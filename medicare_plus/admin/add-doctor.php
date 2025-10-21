<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Doctor - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('admin');
    
    $error = '';
    $success = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $specialization_id = intval($_POST['specialization_id'] ?? 0);
        $qualification = sanitizeInput($_POST['qualification'] ?? '');
        $experience_years = intval($_POST['experience_years'] ?? 0);
        $consultation_fee = floatval($_POST['consultation_fee'] ?? 0);
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($qualification)) {
            $error = 'Please fill in all required fields.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            $conn = getDBConnection();
            
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Username or email already exists.';
            } else {
                // Insert into users table
                $hashed_password = hashPassword($password);
                $role = 'doctor';
                
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // Insert into doctors table
                    $stmt = $conn->prepare("INSERT INTO doctors (user_id, first_name, last_name, specialization_id, qualification, experience_years, consultation_fee, phone, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issisiiss", $user_id, $first_name, $last_name, $specialization_id, $qualification, $experience_years, $consultation_fee, $phone, $bio);
                    
                    if ($stmt->execute()) {
                        setSuccessMessage('Doctor added successfully!');
                        header('Location: doctors.php');
                        exit();
                    } else {
                        $error = 'Failed to add doctor. Please try again.';
                    }
                } else {
                    $error = 'Failed to add doctor. Please try again.';
                }
            }
            
            $stmt->close();
            closeDBConnection($conn);
        }
    }
    
    // Get specializations
    $conn = getDBConnection();
    $specializations = $conn->query("SELECT * FROM specializations ORDER BY name");
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
            <h2 style="margin-bottom: 30px; color: var(--primary-color);">Add New Doctor</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="add-doctor.php">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="specialization_id">Specialization</label>
                    <select id="specialization_id" name="specialization_id" class="form-control">
                        <option value="">Select Specialization</option>
                        <?php while ($spec = $specializations->fetch_assoc()): ?>
                            <option value="<?php echo $spec['specialization_id']; ?>" 
                                <?php echo (($_POST['specialization_id'] ?? '') == $spec['specialization_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($spec['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="qualification">Qualification *</label>
                    <input type="text" id="qualification" name="qualification" class="form-control" required 
                           placeholder="e.g., MBBS, MD, MS"
                           value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="experience_years">Years of Experience</label>
                    <input type="number" id="experience_years" name="experience_years" class="form-control" min="0"
                           value="<?php echo htmlspecialchars($_POST['experience_years'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="consultation_fee">Consultation Fee (Rs.)</label>
                    <input type="number" id="consultation_fee" name="consultation_fee" class="form-control" min="0" step="0.01"
                           value="<?php echo htmlspecialchars($_POST['consultation_fee'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio / About Doctor</label>
                    <textarea id="bio" name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                </div>
                
                <div class="d-flex gap-10">
                    <button type="submit" class="btn btn-primary">Add Doctor</button>
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
