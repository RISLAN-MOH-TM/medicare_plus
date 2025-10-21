<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset User Password - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('admin');
    
    $conn = getDBConnection();
    $error = '';
    $success = '';
    
    // Handle password reset
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
        $search_term = sanitizeInput($_POST['search_term'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($search_term) || empty($new_password)) {
            $error = 'Please fill in all required fields.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            // Search for user by username or email
            $stmt = $conn->prepare("SELECT user_id, username, email, role FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $search_term, $search_term);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = 'User not found. Please check the username or email.';
            } else {
                $user = $result->fetch_assoc();
                
                // Hash the new password
                $hashed_password = hashPassword($new_password);
                
                // Update password
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $user['user_id']);
                
                if ($stmt->execute()) {
                    $success = "Password successfully reset for user: <strong>" . htmlspecialchars($user['username']) . "</strong> (" . htmlspecialchars($user['email']) . ")";
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            }
            
            $stmt->close();
        }
    }
    
    // Get all users for the table
    $users = $conn->query("
        SELECT u.user_id, u.username, u.email, u.role, u.is_active, u.created_at,
               CASE 
                   WHEN u.role = 'patient' THEN CONCAT(p.first_name, ' ', p.last_name)
                   WHEN u.role = 'doctor' THEN CONCAT('Dr. ', d.first_name, ' ', d.last_name)
                   ELSE 'Admin'
               END as full_name
        FROM users u
        LEFT JOIN patients p ON u.user_id = p.user_id
        LEFT JOIN doctors d ON u.user_id = d.user_id
        ORDER BY u.created_at DESC
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
                        <li><a href="doctors.php">Manage Doctors</a></li>
                        <li><a href="patients.php">Manage Patients</a></li>
                        <li><a href="appointments.php">Appointments</a></li>
                        <li><a href="reset-password.php">Reset Password</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="container">
            <h1>Reset User Password</h1>

            <div class="card" style="max-width: 600px; margin: 0 auto 30px;">
                <h2 style="margin-bottom: 20px; color: var(--primary-color);">🔐 Reset Password Form</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="reset-password.php">
                    <div class="form-group">
                        <label for="search_term">Username or Email *</label>
                        <input type="text" id="search_term" name="search_term" class="form-control" required 
                               placeholder="Enter username or email address"
                               value="<?php echo htmlspecialchars($_POST['search_term'] ?? ''); ?>">
                        <small style="color: var(--dark-gray); display: block; margin-top: 5px;">
                            Enter the username OR email of the user whose password you want to reset
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required 
                               minlength="6" placeholder="Enter new password (min 6 characters)">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                               minlength="6" placeholder="Re-enter new password">
                    </div>
                    
                    <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <strong>⚠️ Important:</strong>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li>This will immediately change the user's password</li>
                            <li>The user will need to use the new password to login</li>
                            <li>Make sure to communicate the new password securely to the user</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" name="reset_password" class="btn btn-warning" 
                                onclick="return confirm('Are you sure you want to reset this user\'s password?');">
                            Reset Password
                        </button>
                        <a href="dashboard.php" class="btn btn-danger">Cancel</a>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>All System Users</h2>
                <div class="form-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search users by name, username, email, or role...">
                </div>
                
                <?php if ($users->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Quick Reset</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $user['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span style="padding: 5px 10px; border-radius: 3px; font-size: 0.85rem; font-weight: 600;
                                                background-color: <?php 
                                                    echo $user['role'] === 'admin' ? '#e74c3c' : 
                                                        ($user['role'] === 'doctor' ? '#3498db' : '#27ae60'); 
                                                ?>; color: white;">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="padding: 5px 10px; border-radius: 3px; font-size: 0.85rem; font-weight: 600;
                                                background-color: <?php echo $user['is_active'] ? '#27ae60' : '#e74c3c'; ?>; color: white;">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-warning" style="padding: 5px 10px; font-size: 0.85rem;"
                                                    onclick="fillResetForm('<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['email']); ?>')">
                                                Reset
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">No users found.</p>
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
        searchTable('searchInput', 'usersTable');
        
        function fillResetForm(username, email) {
            document.getElementById('search_term').value = username;
            document.getElementById('new_password').focus();
            window.scrollTo({top: 0, behavior: 'smooth'});
        }
    </script>
</body>
</html>
