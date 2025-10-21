<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - MediCare Plus</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/includes/auth.php';
    
    $conn = getDBConnection();
    
    // Get all services
    $services = $conn->query("
        SELECT * FROM services 
        WHERE is_active = 1
        ORDER BY category, service_name
    ");
    
    // Group services by category
    $services_by_category = [];
    while ($service = $services->fetch_assoc()) {
        $category = $service['category'] ?? 'Other Services';
        if (!isset($services_by_category[$category])) {
            $services_by_category[$category] = [];
        }
        $services_by_category[$category][] = $service;
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

    <section style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white; padding: 60px 20px; text-align: center;">
        <div class="container">
            <h2 style="font-size: 2.5rem; margin-bottom: 20px;">Our Medical Services</h2>
            <p style="font-size: 1.2rem;">Comprehensive healthcare solutions tailored to your needs</p>
        </div>
    </section>

    <div class="container" style="padding: 60px 20px;">
        <?php foreach ($services_by_category as $category => $category_services): ?>
            <div style="margin-bottom: 60px;">
                <h2 style="color: var(--primary-color); margin-bottom: 30px; padding-bottom: 10px; border-bottom: 3px solid var(--secondary-color);">
                    <?php echo htmlspecialchars($category); ?>
                </h2>
                
                <div class="service-grid">
                    <?php foreach ($category_services as $service): ?>
                        <div class="card">
                            <h3 style="color: var(--secondary-color); margin-bottom: 15px;">
                                <?php echo htmlspecialchars($service['service_name']); ?>
                            </h3>
                            
                            <?php if (!empty($service['description'])): ?>
                                <p style="color: var(--dark-gray); margin-bottom: 20px;">
                                    <?php echo htmlspecialchars($service['description']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div style="margin-top: 20px;">
                                <?php if ($service['base_price']): ?>
                                    <p style="font-size: 1.2rem; font-weight: 600; color: var(--primary-color);">
                                        Starting from: Rs. <?php echo number_format($service['base_price'], 2); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($service['duration_minutes']): ?>
                                    <p style="color: var(--dark-gray); margin-top: 5px;">
                                        Duration: <?php echo $service['duration_minutes']; ?> minutes
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-top: 20px;">
                                <?php if (isLoggedIn() && hasRole('patient')): ?>
                                    <a href="doctors.php" class="btn btn-primary">Book Appointment</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary">Login to Book</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($services_by_category)): ?>
            <div class="card">
                <p style="text-align: center; color: var(--dark-gray); padding: 40px;">
                    No services available at the moment. Please check back later.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <section class="services" style="background-color: var(--light-gray);">
        <div class="container">
            <h2>Why Choose Our Services?</h2>
            <div class="service-grid">
                <div class="card">
                    <div class="service-icon">🏆</div>
                    <h3>World-Class Care</h3>
                    <p>State-of-the-art medical facilities and equipment</p>
                </div>
                <div class="card">
                    <div class="service-icon">👨‍⚕️</div>
                    <h3>Expert Team</h3>
                    <p>Highly qualified and experienced medical professionals</p>
                </div>
                <div class="card">
                    <div class="service-icon">💰</div>
                    <h3>Affordable Pricing</h3>
                    <p>Quality healthcare at competitive prices</p>
                </div>
                <div class="card">
                    <div class="service-icon">📅</div>
                    <h3>Easy Scheduling</h3>
                    <p>Book appointments online at your convenience</p>
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
