<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MediCare Plus</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/includes/auth.php';
    
    // Redirect if already logged in
    if (isLoggedIn()) {
        $role = getCurrentUserRole();
        if ($role === 'admin') {
            header('Location: admin/dashboard.php');
        } elseif ($role === 'doctor') {
            header('Location: doctor/dashboard.php');
        } else {
            header('Location: patient/dashboard.php');
        }
        exit();
    }
    
    $error = getErrorMessage();
    $success = getSuccessMessage();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $conn = getDBConnection();
            
            $stmt = $conn->prepare("SELECT user_id, username, email, password, role, is_active FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if ($user['is_active'] == 0) {
                    $error = 'Your account has been deactivated. Please contact administrator.';
                } elseif (verifyPassword($password, $user['password'])) {
                    loginUser($user['user_id'], $user['username'], $user['email'], $user['role']);
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } elseif ($user['role'] === 'doctor') {
                        header('Location: doctor/dashboard.php');
                    } else {
                        header('Location: patient/dashboard.php');
                    }
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
            
            $stmt->close();
            closeDBConnection($conn);
        }
    }
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="register.php">Register</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container" style="padding: 80px 20px;">
        <div class="card" style="max-width: 450px; margin: 0 auto;">
            <h2 style="text-align: center; margin-bottom: 30px; color: var(--primary-color);">Login to MediCare Plus</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                
                <p style="text-align: center; margin-top: 20px;">
                    Don't have an account? <a href="register.php" style="color: var(--secondary-color);">Register here</a>
                </p>
            </form>
            
            
        </div>
    </div>

    <footer style="position: fixed; bottom: 0; width: 100%;">
        <div class="container">
            <p>&copy; 2025 MediCare Plus. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
