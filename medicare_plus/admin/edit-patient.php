<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('admin');
    
    $conn = getDBConnection();
    $error = '';
    $success = '';
    
    // Get patient ID from URL
    $patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($patient_id === 0) {
        setErrorMessage('Invalid patient ID.');
        header('Location: patients.php');
        exit();
    }
    
    // Get patient details
    $stmt = $conn->prepare("
        SELECT p.*, u.user_id, u.username, u.email, u.is_active
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.patient_id = ?
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        setErrorMessage('Patient not found.');
        header('Location: patients.php');
        exit();
    }
    
    $patient = $result->fetch_assoc();
    $stmt->close();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_credentials'])) {
            // Update username/email/password
            $new_username = sanitizeInput($_POST['username'] ?? '');
            $new_email = sanitizeInput($_POST['email'] ?? '');
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($new_username) || empty($new_email)) {
                $error = 'Username and email are required.';
            } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email format.';
            } elseif (!empty($new_password) && $new_password !== $confirm_password) {
                $error = 'Passwords do not match.';
            } elseif (!empty($new_password) && strlen($new_password) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } else {
                // Check if username or email already exists for other users
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
                $stmt->bind_param("ssi", $new_username, $new_email, $patient['user_id']);
                $stmt->execute();
                $check_result = $stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $error = 'Username or email already exists for another user.';
                } else {
                    // Update credentials
                    if (!empty($new_password)) {
                        $hashed_password = hashPassword($new_password);
                        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ?");
                        $stmt->bind_param("sssi", $new_username, $new_email, $hashed_password, $patient['user_id']);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
                        $stmt->bind_param("ssi", $new_username, $new_email, $patient['user_id']);
                    }
                    
                    if ($stmt->execute()) {
                        $success = 'Patient credentials updated successfully!';
                        // Refresh patient data
                        $patient['username'] = $new_username;
                        $patient['email'] = $new_email;
                    } else {
                        $error = 'Failed to update credentials. Please try again.';
                    }
                }
                $stmt->close();
            }
        } elseif (isset($_POST['update_profile'])) {
            // Update profile information
            $first_name = sanitizeInput($_POST['first_name'] ?? '');
            $last_name = sanitizeInput($_POST['last_name'] ?? '');
            $phone = sanitizeInput($_POST['phone'] ?? '');
            $date_of_birth = sanitizeInput($_POST['date_of_birth'] ?? '');
            $gender = sanitizeInput($_POST['gender'] ?? '');
            $blood_group = sanitizeInput($_POST['blood_group'] ?? '');
            $address = sanitizeInput($_POST['address'] ?? '');
            $emergency_contact = sanitizeInput($_POST['emergency_contact'] ?? '');
            
            if (empty($first_name) || empty($last_name)) {
                $error = 'First name and last name are required.';
            } else {
                $stmt = $conn->prepare("
                    UPDATE patients 
                    SET first_name = ?, last_name = ?, phone = ?, date_of_birth = ?, 
                        gender = ?, blood_group = ?, address = ?, emergency_contact = ?
                    WHERE patient_id = ?
                ");
                $stmt->bind_param("ssssssssi", $first_name, $last_name, $phone, $date_of_birth, 
                                  $gender, $blood_group, $address, $emergency_contact, $patient_id);
                
                if ($stmt->execute()) {
                    $success = 'Patient profile updated successfully!';
                    // Refresh patient data
                    $patient['first_name'] = $first_name;
                    $patient['last_name'] = $last_name;
                    $patient['phone'] = $phone;
                    $patient['date_of_birth'] = $date_of_birth;
                    $patient['gender'] = $gender;
                    $patient['blood_group'] = $blood_group;
                    $patient['address'] = $address;
                    $patient['emergency_contact'] = $emergency_contact;
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
                $stmt->close();
            }
        } elseif (isset($_POST['toggle_status'])) {
            // Toggle active status
            $new_status = $patient['is_active'] ? 0 : 1;
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $new_status, $patient['user_id']);
            
            if ($stmt->execute()) {
                $success = 'Patient status updated successfully!';
                $patient['is_active'] = $new_status;
            } else {
                $error = 'Failed to update status. Please try again.';
            }
            $stmt->close();
        }
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
            <div class="dashboard-header">
                <h1>Edit Patient Information</h1>
                <p>Patient ID: #<?php echo $patient_id; ?> - <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Account Status Card -->
            <div class="card">
                <h2>Account Status</h2>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background-color: #f8f9fa; border-radius: 8px; margin-top: 15px;">
                    <div>
                        <p><strong>Current Status:</strong></p>
                        <span style="padding: 8px 15px; border-radius: 3px; font-weight: 600;
                            background-color: <?php echo $patient['is_active'] ? '#27ae60' : '#e74c3c'; ?>; color: white;">
                            <?php echo $patient['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <form method="POST" style="margin: 0;">
                        <button type="submit" name="toggle_status" 
                                class="btn <?php echo $patient['is_active'] ? 'btn-danger' : 'btn-success'; ?>"
                                onclick="return confirm('Are you sure you want to <?php echo $patient['is_active'] ? 'deactivate' : 'activate'; ?> this account?');">
                            <?php echo $patient['is_active'] ? 'Deactivate Account' : 'Activate Account'; ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Login Credentials Card -->
            <div class="card">
                <h2>🔐 Login Credentials</h2>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" class="form-control" required 
                                   value="<?php echo htmlspecialchars($patient['username']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-control" required 
                                   value="<?php echo htmlspecialchars($patient['email']); ?>">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label for="new_password">New Password (leave blank to keep current)</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" 
                                   minlength="6" placeholder="Enter new password (optional)">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   minlength="6" placeholder="Re-enter new password">
                        </div>
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" name="update_credentials" class="btn btn-warning">Update Credentials</button>
                        <a href="patients.php" class="btn btn-danger">Cancel</a>
                    </div>
                </form>
            </div>

            <!-- Profile Information Card -->
            <div class="card">
                <h2>👤 Profile Information</h2>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($patient['first_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($patient['last_name']); ?>">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" class="form-control">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($patient['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($patient['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($patient['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="blood_group">Blood Group</label>
                            <select id="blood_group" name="blood_group" class="form-control">
                                <option value="">Select Blood Group</option>
                                <option value="A+" <?php echo ($patient['blood_group'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo ($patient['blood_group'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo ($patient['blood_group'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo ($patient['blood_group'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo ($patient['blood_group'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo ($patient['blood_group'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo ($patient['blood_group'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo ($patient['blood_group'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="emergency_contact">Emergency Contact</label>
                        <input type="tel" id="emergency_contact" name="emergency_contact" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['emergency_contact'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        <a href="patients.php" class="btn btn-danger">Cancel</a>
                    </div>
                </form>
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
