<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('admin');
    
    $conn = getDBConnection();
    $doctor_id = intval($_GET['id'] ?? 0);
    
    if ($doctor_id === 0) {
        setErrorMessage('Invalid doctor ID.');
        header('Location: doctors.php');
        exit();
    }
    
    // Get doctor info
    $stmt = $conn->prepare("
        SELECT d.*, u.username, u.email, u.is_active
        FROM doctors d
        JOIN users u ON d.user_id = u.user_id
        WHERE d.doctor_id = ?
    ");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        setErrorMessage('Doctor not found.');
        header('Location: doctors.php');
        exit();
    }
    
    $doctor = $result->fetch_assoc();
    $error = '';
    $success = '';
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $specialization_id = intval($_POST['specialization_id'] ?? 0);
        $qualification = sanitizeInput($_POST['qualification'] ?? '');
        $experience_years = intval($_POST['experience_years'] ?? 0);
        $consultation_fee = floatval($_POST['consultation_fee'] ?? 0);
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $new_password = $_POST['password'] ?? '';
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($qualification)) {
            $error = 'Please fill in all required fields.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email is already used by another user
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->bind_param("si", $email, $doctor['user_id']);
            $stmt->execute();
            $existing = $stmt->get_result();
            
            if ($existing->num_rows > 0) {
                $error = 'Email already exists for another user.';
            } else {
                // Update user email and status
                $stmt = $conn->prepare("UPDATE users SET email = ?, is_active = ? WHERE user_id = ?");
                $stmt->bind_param("sii", $email, $is_active, $doctor['user_id']);
                $stmt->execute();
                
                // Update password if provided
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        $error = 'Password must be at least 6 characters long.';
                    } else {
                        $hashed_password = hashPassword($new_password);
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                        $stmt->bind_param("si", $hashed_password, $doctor['user_id']);
                        $stmt->execute();
                    }
                }
                
                if (empty($error)) {
                    // Update doctor details
                    $stmt = $conn->prepare("
                        UPDATE doctors 
                        SET first_name = ?, last_name = ?, specialization_id = ?, qualification = ?, 
                            experience_years = ?, consultation_fee = ?, phone = ?, bio = ?
                        WHERE doctor_id = ?
                    ");
                    $stmt->bind_param("ssisisssi", $first_name, $last_name, $specialization_id, $qualification, 
                                     $experience_years, $consultation_fee, $phone, $bio, $doctor_id);
                    
                    if ($stmt->execute()) {
                        setSuccessMessage('Doctor updated successfully!');
                        header('Location: doctors.php');
                        exit();
                    } else {
                        $error = 'Failed to update doctor. Please try again.';
                    }
                }
            }
        }
    }
    
    // Get specializations
    $specializations = $conn->query("SELECT * FROM specializations ORDER BY name");
    
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
                        <li><a href="doctors.php">Manage Doctors</a></li>
                        <li><a href="patients.php">Manage Patients</a></li>
                        <li><a href="reset-password.php">Reset Password</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container" style="padding: 60px 20px;">
        <div class="card" style="max-width: 700px; margin: 0 auto;">
            <h2 style="margin-bottom: 30px; color: var(--primary-color);">Edit Doctor</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="edit-doctor.php?id=<?php echo $doctor_id; ?>">
                <div class="form-group">
                    <label for="username">Username (Cannot be changed)</label>
                    <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($doctor['username']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           value="<?php echo htmlspecialchars($doctor['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">New Password (Leave blank to keep current)</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter new password only if you want to change it">
                </div>
                
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required 
                           value="<?php echo htmlspecialchars($doctor['first_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required 
                           value="<?php echo htmlspecialchars($doctor['last_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="specialization_id">Specialization</label>
                    <select id="specialization_id" name="specialization_id" class="form-control">
                        <option value="">Select Specialization</option>
                        <?php while ($spec = $specializations->fetch_assoc()): ?>
                            <option value="<?php echo $spec['specialization_id']; ?>" 
                                <?php echo ($doctor['specialization_id'] == $spec['specialization_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($spec['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="qualification">Qualification *</label>
                    <input type="text" id="qualification" name="qualification" class="form-control" required 
                           placeholder="e.g., MBBS, MD, MS"
                           value="<?php echo htmlspecialchars($doctor['qualification']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="experience_years">Years of Experience</label>
                    <input type="number" id="experience_years" name="experience_years" class="form-control" min="0"
                           value="<?php echo $doctor['experience_years']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="consultation_fee">Consultation Fee (Rs.)</label>
                    <input type="number" id="consultation_fee" name="consultation_fee" class="form-control" min="0" step="0.01"
                           value="<?php echo $doctor['consultation_fee']; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($doctor['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio / About Doctor</label>
                    <textarea id="bio" name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($doctor['bio'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="is_active" <?php echo $doctor['is_active'] ? 'checked' : ''; ?>>
                        Active Account
                    </label>
                </div>
                
                <div class="d-flex gap-10">
                    <button type="submit" class="btn btn-primary">Update Doctor</button>
                    <a href="doctors.php" class="btn btn-danger">Cancel</a>
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
