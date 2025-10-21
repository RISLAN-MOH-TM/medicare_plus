<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Doctors - MediCare Plus</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/includes/auth.php';
    
    $conn = getDBConnection();
    
    // Get search and filter parameters
    $search = sanitizeInput($_GET['search'] ?? '');
    $specialization_filter = intval($_GET['specialization'] ?? 0);
    
    // Build query
    $query = "
        SELECT d.*, s.name as specialization_name, u.email
        FROM doctors d
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        JOIN users u ON d.user_id = u.user_id
        WHERE u.is_active = 1
    ";
    
    if (!empty($search)) {
        $search_param = "%$search%";
        $query .= " AND (d.first_name LIKE '$search_param' OR d.last_name LIKE '$search_param' OR s.name LIKE '$search_param')";
    }
    
    if ($specialization_filter > 0) {
        $query .= " AND d.specialization_id = $specialization_filter";
    }
    
    $query .= " ORDER BY d.rating DESC, d.doctor_id DESC";
    
    $doctors = $conn->query($query);
    
    // Get all specializations for filter
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
            <h2 style="font-size: 2.5rem; margin-bottom: 20px;">Find Your Doctor</h2>
            <p style="font-size: 1.2rem;">Expert healthcare professionals at your service</p>
        </div>
    </section>

    <div class="container" style="padding: 60px 20px;">
        <div class="card">
            <h3 style="margin-bottom: 20px;">Search & Filter Doctors</h3>
            <form method="GET" action="doctors.php" class="d-flex gap-10" style="flex-wrap: wrap;">
                <input type="text" name="search" class="form-control" placeholder="Search by name or specialization..." 
                       value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 200px;">
                <select name="specialization" class="form-control" style="flex: 1; min-width: 200px;">
                    <option value="">All Specializations</option>
                    <?php while ($spec = $specializations->fetch_assoc()): ?>
                        <option value="<?php echo $spec['specialization_id']; ?>" 
                            <?php echo ($specialization_filter == $spec['specialization_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($spec['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="doctors.php" class="btn btn-danger">Clear</a>
            </form>
        </div>

        <?php if ($doctors->num_rows > 0): ?>
            <div class="service-grid">
                <?php while ($doctor = $doctors->fetch_assoc()): ?>
                    <div class="card">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 120px; height: 120px; margin: 0 auto; background: linear-gradient(135deg, var(--secondary-color), var(--primary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                👨‍⚕️
                            </div>
                        </div>
                        <h3 style="text-align: center; margin-bottom: 10px; color: var(--primary-color);">
                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                        </h3>
                        <p style="text-align: center; color: var(--secondary-color); font-weight: 600; margin-bottom: 15px;">
                            <?php echo htmlspecialchars($doctor['specialization_name'] ?? 'General Medicine'); ?>
                        </p>
                        
                        <div style="margin: 15px 0;">
                            <p><strong>Qualification:</strong> <?php echo htmlspecialchars($doctor['qualification']); ?></p>
                            <p><strong>Experience:</strong> <?php echo $doctor['experience_years'] ?? 0; ?> years</p>
                            <p><strong>Consultation Fee:</strong> Rs. <?php echo number_format($doctor['consultation_fee'] ?? 0, 2); ?></p>
                            <p><strong>Rating:</strong> <?php echo number_format($doctor['rating'], 1); ?> ⭐ (<?php echo $doctor['total_reviews']; ?> reviews)</p>
                        </div>
                        
                        <?php if (!empty($doctor['bio'])): ?>
                            <p style="color: var(--dark-gray); font-size: 0.9rem; margin: 15px 0;">
                                <?php echo htmlspecialchars(substr($doctor['bio'], 0, 100)) . (strlen($doctor['bio']) > 100 ? '...' : ''); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div style="margin-top: 20px; text-align: center;">
                            <?php if (isLoggedIn() && hasRole('patient')): ?>
                                <a href="patient/book-appointment.php?doctor_id=<?php echo $doctor['doctor_id']; ?>" class="btn btn-primary">Book Appointment</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">Login to Book</a>
                            <?php endif; ?>
                            <a href="doctor-profile.php?id=<?php echo $doctor['doctor_id']; ?>" class="btn btn-success">View Profile</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <p style="text-align: center; color: var(--dark-gray); padding: 40px;">
                    No doctors found matching your criteria. Please try a different search.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 MediCare Plus. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
