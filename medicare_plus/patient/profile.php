<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Patient Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('patient');
    
    $conn = getDBConnection();
    $user_id = getCurrentUserId();
    
    // Get patient info
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.email, u.created_at
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $patient_result = $stmt->get_result();
    
    if ($patient_result->num_rows === 0) {
        setErrorMessage('Patient profile not found.');
        header('Location: ../logout.php');
        exit();
    }
    
    $patient = $patient_result->fetch_assoc();
    $error = '';
    $success = getSuccessMessage();
    
    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $date_of_birth = sanitizeInput($_POST['date_of_birth'] ?? '');
        $gender = sanitizeInput($_POST['gender'] ?? '');
        $blood_group = sanitizeInput($_POST['blood_group'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $emergency_contact = sanitizeInput($_POST['emergency_contact'] ?? '');
        $medical_history = sanitizeInput($_POST['medical_history'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Update patient info
        $stmt = $conn->prepare("
            UPDATE patients 
            SET phone = ?, date_of_birth = ?, gender = ?, blood_group = ?, 
                address = ?, emergency_contact = ?, medical_history = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("sssssssi", $phone, $date_of_birth, $gender, $blood_group, 
                          $address, $emergency_contact, $medical_history, $user_id);
        
        if ($stmt->execute()) {
            // Update password if provided
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = 'Passwords do not match.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Password must be at least 6 characters long.';
                } else {
                    $hashed_password = hashPassword($new_password);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    $stmt->execute();
                }
            }
            
            if (empty($error)) {
                setSuccessMessage('Profile updated successfully!');
                header('Location: profile.php');
                exit();
            }
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
    }
    
    $stmt->close();
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
                        <li><a href="../doctors.php">Find Doctors</a></li>
                        <li><a href="dashboard.php">My Dashboard</a></li>
                        <li><a href="messages.php">Messages</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container" style="padding: 60px 20px;">
        <div class="card" style="max-width: 900px; margin: 0 auto;">
            <h2 style="margin-bottom: 30px; color: var(--primary-color);">My Profile</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div style="background-color: var(--light-gray); padding: 25px; border-radius: 10px; margin-bottom: 30px;">
                <h3 style="margin-bottom: 20px; color: var(--primary-color);">Account Information</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($patient['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                    </div>
                    <div>
                        <p><strong>Patient ID:</strong> #<?php echo $patient['patient_id']; ?></p>
                        <p><strong>Member Since:</strong> <?php echo date('M d, Y', strtotime($patient['created_at'])); ?></p>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="profile.php">
                <h3 style="margin-bottom: 20px; color: var(--primary-color);">Update Personal Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                               value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
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
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="emergency_contact">Emergency Contact</label>
                    <input type="tel" id="emergency_contact" name="emergency_contact" class="form-control" 
                           value="<?php echo htmlspecialchars($patient['emergency_contact'] ?? ''); ?>" 
                           placeholder="Emergency contact phone number">
                </div>
                
                <div class="form-group">
                    <label for="medical_history">Medical History / Allergies</label>
                    <textarea id="medical_history" name="medical_history" class="form-control" rows="4" 
                              placeholder="List any chronic conditions, allergies, or important medical information..."><?php echo htmlspecialchars($patient['medical_history'] ?? ''); ?></textarea>
                </div>
                
                <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--light-gray);">
                
                <h3 style="margin-bottom: 20px; color: var(--primary-color);">Change Password</h3>
                <p style="color: var(--dark-gray); margin-bottom: 20px;">Leave blank if you don't want to change your password.</p>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" 
                               placeholder="Minimum 6 characters">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Re-enter new password">
                    </div>
                </div>
                
                <div class="d-flex gap-10" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
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
