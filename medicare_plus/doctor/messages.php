<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('doctor');
    
    $conn = getDBConnection();
    $user_id = getCurrentUserId();
    
    $success = getSuccessMessage();
    $error = getErrorMessage();
    
    // Handle sending/replying to message
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
            $stmt->close();
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
               u.user_id as sender_user_id,
               CONCAT(p.first_name, ' ', p.last_name) as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        JOIN patients p ON u.user_id = p.user_id
        WHERE m.receiver_id = $user_id
        ORDER BY m.sent_at DESC
    ");
    
    // Get sent messages
    $sent = $conn->query("
        SELECT m.*, 
               u.username as receiver_username,
               CONCAT(p.first_name, ' ', p.last_name) as receiver_name
        FROM messages m
        JOIN users u ON m.receiver_id = u.user_id
        LEFT JOIN patients p ON u.user_id = p.user_id
        WHERE m.sender_id = $user_id
        ORDER BY m.sent_at DESC
    ");
    
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
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="appointments.php">All Appointments</a></li>
                        <li><a href="patients.php">My Patients</a></li>
                        <li><a href="medical-reports.php">Medical Reports</a></li>
                        <li><a href="messages.php">Messages</a></li>
                        <li><a href="profile.php">My Profile</a></li>
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

            <!-- Reply Form (Hidden by default) -->
            <div class="card" id="replyFormCard" style="display: none;">
                <h2>Reply to Message</h2>
                <form method="POST" action="messages.php">
                    <input type="hidden" id="reply_receiver_id" name="receiver_id">
                    
                    <div class="form-group">
                        <label for="reply_to">To:</label>
                        <input type="text" id="reply_to" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" class="form-control" rows="4" required></textarea>
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" name="send_message" class="btn btn-primary">Send Reply</button>
                        <button type="button" class="btn btn-danger" onclick="hideReplyForm()">Cancel</button>
                    </div>
                </form>
            </div>

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
                                            <a href="#" class="btn btn-success" style="padding: 5px 10px; font-size: 0.85rem;"
                                               onclick="showReplyForm(<?php echo $msg['sender_user_id']; ?>, '<?php echo htmlspecialchars(addslashes($msg['sender_name'])); ?>', 'Re: <?php echo htmlspecialchars(addslashes($msg['subject'])); ?>'); return false;">Reply</a>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($msg = $sent->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($msg['receiver_name'] ?? 'Admin'); ?></td>
                                        <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($msg['message'], 0, 60)) . (strlen($msg['message']) > 60 ? '...' : ''); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($msg['sent_at'])); ?></td>
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
    <script>
        function showReplyForm(receiverId, receiverName, subject) {
            document.getElementById('reply_receiver_id').value = receiverId;
            document.getElementById('reply_to').value = receiverName;
            document.getElementById('subject').value = subject;
            document.getElementById('message').value = '';
            document.getElementById('replyFormCard').style.display = 'block';
            document.getElementById('replyFormCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function hideReplyForm() {
            document.getElementById('replyFormCard').style.display = 'none';
            document.getElementById('reply_receiver_id').value = '';
            document.getElementById('reply_to').value = '';
            document.getElementById('subject').value = '';
            document.getElementById('message').value = '';
        }
    </script>
</body>
</html>
