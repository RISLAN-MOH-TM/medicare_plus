<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile - MediCare Plus</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/includes/auth.php';
    
    $doctor_id = intval($_GET['id'] ?? 0);
    
    if ($doctor_id === 0) {
        setErrorMessage('Invalid doctor ID.');
        header('Location: /doctors.php');
        exit();
    }
    
    $conn = getDBConnection();
    
    // Get doctor details
    $stmt = $conn->prepare("
        SELECT d.*, s.name as specialization_name, u.email
        FROM doctors d
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        JOIN users u ON d.user_id = u.user_id
        WHERE d.doctor_id = ? AND u.is_active = 1
    ");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor_result = $stmt->get_result();
    
    if ($doctor_result->num_rows === 0) {
        setErrorMessage('Doctor not found.');
        header('Location: /doctors.php');
        exit();
    }
    
    $doctor = $doctor_result->fetch_assoc();
    
    // Get doctor reviews
    $reviews = $conn->query("
        SELECT r.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name
        FROM reviews r
        JOIN patients p ON r.patient_id = p.patient_id
        WHERE r.doctor_id = $doctor_id
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="doctors.php">Our Doctors</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container" style="padding: 60px 20px;">
        <div class="card">
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 40px;">
                <div style="text-align: center;">
                    <div style="width: 200px; height: 200px; margin: 0 auto; background: linear-gradient(135deg, var(--secondary-color), var(--primary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 5rem;">
                        👨‍⚕️
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <div style="background-color: var(--light-gray); padding: 20px; border-radius: 10px;">
                            <h3 style="font-size: 2rem; color: var(--primary-color); margin-bottom: 10px;">
                                <?php echo number_format($doctor['rating'], 1); ?> ⭐
                            </h3>
                            <p style="color: var(--dark-gray);">
                                Based on <?php echo $doctor['total_reviews']; ?> reviews
                            </p>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <?php if (isLoggedIn() && hasRole('patient')): ?>
                                <a href="patient/book-appointment.php?doctor_id=<?php echo $doctor_id; ?>" 
                                   class="btn btn-primary" style="width: 100%;">Book Appointment</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary" style="width: 100%;">Login to Book</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h1 style="color: var(--primary-color); margin-bottom: 10px;">
                        Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                    </h1>
                    <h3 style="color: var(--secondary-color); margin-bottom: 30px;">
                        <?php echo htmlspecialchars($doctor['specialization_name'] ?? 'General Medicine'); ?>
                    </h3>
                    
                    <div style="margin-bottom: 30px;">
                        <h3 style="margin-bottom: 15px;">Professional Information</h3>
                        <p><strong>Qualification:</strong> <?php echo htmlspecialchars($doctor['qualification']); ?></p>
                        <p><strong>Experience:</strong> <?php echo $doctor['experience_years']; ?> years</p>
                        <p><strong>Consultation Fee:</strong> Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></p>
                        <?php if (!empty($doctor['phone'])): ?>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($doctor['bio'])): ?>
                        <div style="margin-bottom: 30px;">
                            <h3 style="margin-bottom: 15px;">About</h3>
                            <p style="line-height: 1.8; color: var(--dark-gray);">
                                <?php echo nl2br(htmlspecialchars($doctor['bio'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 30px;">Patient Reviews</h2>
            
            <?php if ($reviews->num_rows > 0): ?>
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div style="border-bottom: 1px solid var(--light-gray); padding: 20px 0;">
                        <div class="d-flex justify-between align-center" style="margin-bottom: 10px;">
                            <div>
                                <strong><?php echo htmlspecialchars($review['patient_name']); ?></strong>
                                <span style="margin-left: 10px; color: var(--warning-color);">
                                    <?php echo str_repeat('⭐', $review['rating']); ?>
                                </span>
                            </div>
                            <span style="color: var(--dark-gray); font-size: 0.9rem;">
                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                            </span>
                        </div>
                        <?php if (!empty($review['comment'])): ?>
                            <p style="color: var(--dark-gray); line-height: 1.6;">
                                <?php echo htmlspecialchars($review['comment']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: var(--dark-gray); padding: 20px;">
                    No reviews yet. Be the first to review this doctor!
                </p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 MediCare Plus. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
