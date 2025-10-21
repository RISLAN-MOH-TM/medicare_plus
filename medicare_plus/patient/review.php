<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Doctor - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .star-rating {
            font-size: 2rem;
            direction: rtl;
            display: inline-block;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #f39c12;
        }
    </style>
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('patient');
    
    $conn = getDBConnection();
    $user_id = getCurrentUserId();
    
    // Get patient info
    $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $patient_result = $stmt->get_result();
    
    if ($patient_result->num_rows === 0) {
        setErrorMessage('Patient profile not found.');
        header('Location: dashboard.php');
        exit();
    }
    
    $patient_id = $patient_result->fetch_assoc()['patient_id'];
    $appointment_id = intval($_GET['appointment_id'] ?? 0);
    
    if ($appointment_id === 0) {
        setErrorMessage('Invalid appointment.');
        header('Location: dashboard.php');
        exit();
    }
    
    // Get appointment details
    $stmt = $conn->prepare("
        SELECT a.*, 
               d.doctor_id,
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               s.name as specialization
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        WHERE a.appointment_id = ? AND a.patient_id = ? AND a.status = 'completed'
    ");
    $stmt->bind_param("ii", $appointment_id, $patient_id);
    $stmt->execute();
    $appointment_result = $stmt->get_result();
    
    if ($appointment_result->num_rows === 0) {
        setErrorMessage('Appointment not found or not completed.');
        header('Location: dashboard.php');
        exit();
    }
    
    $appointment = $appointment_result->fetch_assoc();
    $doctor_id = $appointment['doctor_id'];
    
    // Check if already reviewed
    $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE appointment_id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $appointment_id, $patient_id);
    $stmt->execute();
    $existing_review = $stmt->get_result();
    
    if ($existing_review->num_rows > 0) {
        setErrorMessage('You have already reviewed this appointment.');
        header('Location: dashboard.php');
        exit();
    }
    
    $error = '';
    $success = '';
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rating = intval($_POST['rating'] ?? 0);
        $comment = sanitizeInput($_POST['comment'] ?? '');
        
        if ($rating < 1 || $rating > 5) {
            $error = 'Please select a rating between 1 and 5 stars.';
        } else {
            // Insert review
            $stmt = $conn->prepare("
                INSERT INTO reviews (patient_id, doctor_id, appointment_id, rating, comment)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiiis", $patient_id, $doctor_id, $appointment_id, $rating, $comment);
            
            if ($stmt->execute()) {
                // Update doctor rating
                $conn->query("
                    UPDATE doctors d
                    SET d.rating = (
                        SELECT AVG(r.rating) 
                        FROM reviews r 
                        WHERE r.doctor_id = d.doctor_id
                    ),
                    d.total_reviews = (
                        SELECT COUNT(*) 
                        FROM reviews r 
                        WHERE r.doctor_id = d.doctor_id
                    )
                    WHERE d.doctor_id = $doctor_id
                ");
                
                setSuccessMessage('Thank you for your review!');
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Failed to submit review. Please try again.';
            }
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
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container" style="padding: 60px 20px;">
        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <h2 style="margin-bottom: 30px; color: var(--primary-color);">Rate Your Experience</h2>
            
            <div style="background-color: var(--light-gray); padding: 20px; border-radius: 5px; margin-bottom: 30px;">
                <h3 style="margin-bottom: 10px;">Appointment Details</h3>
                <p><strong>Doctor:</strong> <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                <p><strong>Specialization:</strong> <?php echo htmlspecialchars($appointment['specialization']); ?></p>
                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="review.php?appointment_id=<?php echo $appointment_id; ?>">
                <div class="form-group">
                    <label>Rating *</label>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" required>
                        <label for="star5">★</label>
                        <input type="radio" id="star4" name="rating" value="4">
                        <label for="star4">★</label>
                        <input type="radio" id="star3" name="rating" value="3">
                        <label for="star3">★</label>
                        <input type="radio" id="star2" name="rating" value="2">
                        <label for="star2">★</label>
                        <input type="radio" id="star1" name="rating" value="1">
                        <label for="star1">★</label>
                    </div>
                    <p style="color: var(--dark-gray); font-size: 0.9rem; margin-top: 10px;">Click on the stars to rate (5 stars = Excellent)</p>
                </div>
                
                <div class="form-group">
                    <label for="comment">Your Review (Optional)</label>
                    <textarea id="comment" name="comment" class="form-control" rows="5" 
                              placeholder="Share your experience with this doctor..."><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                </div>
                
                <div class="d-flex gap-10">
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                    <a href="/patient/dashboard.php" class="btn btn-danger">Cancel</a>
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
