<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('doctor');
    
    $conn = getDBConnection();
    $user_id = getCurrentUserId();
    
    // Get doctor info
    $stmt = $conn->prepare("
        SELECT d.*, u.username, u.email, s.name as specialization_name
        FROM doctors d
        JOIN users u ON d.user_id = u.user_id
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        WHERE d.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $doctor_result = $stmt->get_result();
    
    if ($doctor_result->num_rows === 0) {
        setErrorMessage('Doctor profile not found.');
        header('Location: ../logout.php');
        exit();
    }
    
    $doctor = $doctor_result->fetch_assoc();
    $error = '';
    $success = getSuccessMessage();
    
    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Update doctor info
        $stmt = $conn->prepare("UPDATE doctors SET phone = ?, bio = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $phone, $bio, $user_id);
        
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

    <div class="container" style="padding: 60px 20px;">
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <h2 style="margin-bottom: 30px; color: var(--primary-color);">My Profile</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div style="background-color: var(--light-gray); padding: 25px; border-radius: 10px; margin-bottom: 30px;">
                <h3 style="margin-bottom: 20px; color: var(--primary-color);">Profile Information</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div>
                        <p><strong>Name:</strong> Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($doctor['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?></p>
                    </div>
                    <div>
                        <p><strong>Specialization:</strong> <?php echo htmlspecialchars($doctor['specialization_name'] ?? 'N/A'); ?></p>
                        <p><strong>Qualification:</strong> <?php echo htmlspecialchars($doctor['qualification']); ?></p>
                        <p><strong>Experience:</strong> <?php echo $doctor['experience_years']; ?> years</p>
                    </div>
                    <div>
                        <p><strong>Consultation Fee:</strong> Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></p>
                        <p><strong>Rating:</strong> <?php echo number_format($doctor['rating'], 1); ?> ⭐ (<?php echo $doctor['total_reviews']; ?> reviews)</p>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="profile.php">
                <h3 style="margin-bottom: 20px; color: var(--primary-color);">Update Profile</h3>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio / About</label>
                    <textarea id="bio" name="bio" class="form-control" rows="5"><?php echo htmlspecialchars($doctor['bio'] ?? ''); ?></textarea>
                </div>
                
                <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--light-gray);">
                
                <h3 style="margin-bottom: 20px; color: var(--primary-color);">Change Password</h3>
                <p style="color: var(--dark-gray); margin-bottom: 20px;">Leave blank if you don't want to change your password.</p>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                </div>
                
                <div class="d-flex gap-10">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <a href="dashboard.php" class="btn btn-danger">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 MediCare Plus. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
