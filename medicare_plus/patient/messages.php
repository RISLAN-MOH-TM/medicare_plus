<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireLogin();
    
    $conn = getDBConnection();
    $user_id = getCurrentUserId();
    $role = getCurrentUserRole();
    
    $success = getSuccessMessage();
    $error = getErrorMessage();
    
    // Handle sending message
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
        $receiver_id = intval($_POST['receiver_id']);
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $message_text = sanitizeInput($_POST['message'] ?? '');
        
        if (empty($subject) || empty($message_text)) {
            setErrorMessage('Please fill in all fields.');
        } else {
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $user_id, $receiver_id, $subject, $message_text);
            
            if ($stmt->execute()) {
                setSuccessMessage('Message sent successfully!');
                header('Location: messages.php');
                exit();
            } else {
                setErrorMessage('Failed to send message.');
            }
        }
    }
    
    // Mark message as read
    if (isset($_GET['read'])) {
        $message_id = intval($_GET['read']);
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE message_id = ? AND receiver_id = ?");
        $stmt->bind_param("ii", $message_id, $user_id);
        $stmt->execute();
        header('Location: messages.php');
        exit();
    }
    
    // Get inbox messages
    $inbox = $conn->query("
        SELECT m.*, 
               u.username as sender_username,
               CASE 
                   WHEN u.role = 'doctor' THEN CONCAT('Dr. ', d.first_name, ' ', d.last_name)
                   WHEN u.role = 'patient' THEN CONCAT(p.first_name, ' ', p.last_name)
                   ELSE 'Admin'
               END as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        LEFT JOIN doctors d ON u.user_id = d.user_id AND u.role = 'doctor'
        LEFT JOIN patients p ON u.user_id = p.user_id AND u.role = 'patient'
        WHERE m.receiver_id = $user_id
        ORDER BY m.sent_at DESC
    ");
    
    // Get sent messages
    $sent = $conn->query("
        SELECT m.*, 
               u.username as receiver_username,
               CASE 
                   WHEN u.role = 'doctor' THEN CONCAT('Dr. ', d.first_name, ' ', d.last_name)
                   WHEN u.role = 'patient' THEN CONCAT(p.first_name, ' ', p.last_name)
                   ELSE 'Admin'
               END as receiver_name
        FROM messages m
        JOIN users u ON m.receiver_id = u.user_id
        LEFT JOIN doctors d ON u.user_id = d.user_id AND u.role = 'doctor'
        LEFT JOIN patients p ON u.user_id = p.user_id AND u.role = 'patient'
        WHERE m.sender_id = $user_id
        ORDER BY m.sent_at DESC
    ");
    
    // Get doctors list (for patients to send messages)
    $doctors_list = null;
    if ($role === 'patient') {
        $doctors_list = $conn->query("
            SELECT d.*, u.user_id, CONCAT(d.first_name, ' ', d.last_name) as full_name
            FROM doctors d
            JOIN users u ON d.user_id = u.user_id
            WHERE u.is_active = 1
            ORDER BY d.first_name
        ");
    }
    
    closeDBConnection($conn);
    ?>
    
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <span class="logo-icon">🏥</span>
                    <h1>MediCare Plus</h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <?php if ($role === 'patient'): ?>
                            <li><a href="dashboard.php">Dashboard</a></li>
                        <?php elseif ($role === 'doctor'): ?>
                            <li><a href="../doctor/dashboard.php">Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="container">
            <h1>Messages</h1>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($role === 'patient' && $doctors_list): ?>
                <div class="card">
                    <h2>Send New Message to Doctor</h2>
                    <form method="POST" action="messages.php">
                        <div class="form-group">
                            <label for="receiver_id">Select Doctor</label>
                            <select id="receiver_id" name="receiver_id" class="form-control" required>
                                <option value="">Choose a doctor...</option>
                                <?php while ($doc = $doctors_list->fetch_assoc()): ?>
                                    <option value="<?php echo $doc['user_id']; ?>">
                                        Dr. <?php echo htmlspecialchars($doc['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        
                        <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>Inbox</h2>
                <?php if ($inbox->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($msg = $inbox->fetch_assoc()): ?>
                                    <tr style="<?php echo $msg['is_read'] ? '' : 'background-color: #e8f4f8; font-weight: 600;'; ?>">
                                        <td><?php echo htmlspecialchars($msg['sender_name']); ?></td>
                                        <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($msg['message'], 0, 60)) . (strlen($msg['message']) > 60 ? '...' : ''); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($msg['sent_at'])); ?></td>
                                        <td>
                                            <?php if ($msg['is_read']): ?>
                                                <span style="color: var(--dark-gray);">Read</span>
                                            <?php else: ?>
                                                <span style="color: var(--secondary-color); font-weight: 600;">Unread</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$msg['is_read']): ?>
                                                <a href="?read=<?php echo $msg['message_id']; ?>" 
                                                   class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85rem;">Mark Read</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">No messages in inbox.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Sent Messages</h2>
                <?php if ($sent->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>To</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($msg = $sent->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($msg['receiver_name']); ?></td>
                                        <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($msg['message'], 0, 60)) . (strlen($msg['message']) > 60 ? '...' : ''); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($msg['sent_at'])); ?></td>
                                        <td>
                                            <?php if ($msg['is_read']): ?>
                                                <span style="color: var(--success-color);">Read</span>
                                            <?php else: ?>
                                                <span style="color: var(--dark-gray);">Unread</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">No sent messages.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 MediCare Plus. All rights reserved.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
