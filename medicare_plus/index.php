<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare Plus - Quality Healthcare Services</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/includes/auth.php';
    $error = getErrorMessage();
    $success = getSuccessMessage();
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
                        <li><a href="doctors.php">Our Doctors</a></li>
                        <li><a href="services.php">Services</a></li>
                        <?php if (isLoggedIn()): ?>
                            <?php if (hasRole('admin')): ?>
                                <li><a href="admin/dashboard.php">Admin Dashboard</a></li>
                            <?php elseif (hasRole('doctor')): ?>
                                <li><a href="doctor/dashboard.php">My Dashboard</a></li>
                            <?php elseif (hasRole('patient')): ?>
                                <li><a href="patient/dashboard.php">My Dashboard</a></li>
                            <?php endif; ?>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h2>Welcome to MediCare Plus</h2>
            <p>Your Health, Our Priority - Providing Quality Healthcare Services</p>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-primary">Get Started</a>
                <a href="doctors.php" class="btn btn-success">Find a Doctor</a>
            <?php else: ?>
                <a href="doctors.php" class="btn btn-primary">Book Appointment</a>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($error): ?>
        <div class="container">
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="container">
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        </div>
    <?php endif; ?>

    <section class="services">
        <div class="container">
            <h2>Our Services</h2>
            <div class="service-grid">
                <div class="card service-card">
                    <div class="service-icon">❤️</div>
                    <h3>Cardiology</h3>
                    <p>Comprehensive heart care with state-of-the-art diagnostic facilities and expert cardiologists.</p>
                </div>
                <div class="card service-card">
                    <div class="service-icon">👶</div>
                    <h3>Pediatrics</h3>
                    <p>Specialized care for children with experienced pediatricians ensuring your child's health.</p>
                </div>
                <div class="card service-card">
                    <div class="service-icon">🦴</div>
                    <h3>Orthopedics</h3>
                    <p>Expert treatment for bone, joint, and muscle conditions with advanced surgical options.</p>
                </div>
                <div class="card service-card">
                    <div class="service-icon">🧠</div>
                    <h3>Neurology</h3>
                    <p>Advanced neurological care for brain and nervous system disorders.</p>
                </div>
                <div class="card service-card">
                    <div class="service-icon">📊</div>
                    <h3>Radiology</h3>
                    <p>Modern imaging services including X-ray, CT scan, MRI, and ultrasound.</p>
                </div>
                <div class="card service-card">
                    <div class="service-icon">🚑</div>
                    <h3>Emergency Care</h3>
                    <p>24/7 emergency services with rapid response and critical care facilities.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="services" style="background-color: var(--light-gray);">
        <div class="container">
            <h2>Why Choose MediCare Plus?</h2>
            <div class="service-grid">
                <div class="card">
                    <h3>🏆 Expert Doctors</h3>
                    <p>Our team consists of highly qualified and experienced medical professionals.</p>
                </div>
                <div class="card">
                    <h3>⚡ Quick Appointments</h3>
                    <p>Easy online booking system to schedule appointments at your convenience.</p>
                </div>
                <div class="card">
                    <h3>💻 Digital Reports</h3>
                    <p>Access your medical reports and prescriptions online anytime, anywhere.</p>
                </div>
                <div class="card">
                    <h3>🔒 Secure & Private</h3>
                    <p>Your health data is protected with advanced security measures.</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2025 MediCare Plus. All rights reserved.</p>
            <p>Quality Healthcare for Everyone</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
