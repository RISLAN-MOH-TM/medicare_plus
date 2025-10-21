<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('patient');
    
    $conn = getDBConnection();
    
    // Get patient info
    $user_id = getCurrentUserId();
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
    
    $error = '';
    $success = '';
    $doctor_id = intval($_GET['doctor_id'] ?? $_POST['doctor_id'] ?? 0);
    
    if ($doctor_id === 0) {
        setErrorMessage('Please select a doctor.');
        header('Location: ../doctors.php');
        exit();
    }
    
    // Get doctor info
    $stmt = $conn->prepare("
        SELECT d.*, s.name as specialization_name
        FROM doctors d
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        WHERE d.doctor_id = ?
    ");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor_result = $stmt->get_result();
    
    if ($doctor_result->num_rows === 0) {
        setErrorMessage('Doctor not found.');
        header('Location: ../doctors.php');
        exit();
    }
    
    $doctor = $doctor_result->fetch_assoc();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $appointment_date = sanitizeInput($_POST['appointment_date'] ?? '');
        $appointment_time = sanitizeInput($_POST['appointment_time'] ?? '');
        $reason = sanitizeInput($_POST['reason'] ?? '');
        
        if (empty($appointment_date) || empty($appointment_time)) {
            $error = 'Please select date and time for appointment.';
        } else {
            // Check if date is in the future
            $selected_datetime = strtotime($appointment_date . ' ' . $appointment_time);
            if ($selected_datetime <= time()) {
                $error = 'Please select a future date and time.';
            } else {
                // Check if doctor already has appointment at this time
                $stmt = $conn->prepare("
                    SELECT appointment_id FROM appointments 
                    WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
                    AND status != 'cancelled'
                ");
                $stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
                $stmt->execute();
                $existing = $stmt->get_result();
                
                if ($existing->num_rows > 0) {
                    $error = 'This time slot is already booked. Please select another time.';
                } else {
                    // Insert appointment
                    $stmt = $conn->prepare("
                        INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status)
                        VALUES (?, ?, ?, ?, ?, 'pending')
                    ");
                    $stmt->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason);
                    
                    if ($stmt->execute()) {
                        setSuccessMessage('Appointment booked successfully! Please wait for confirmation.');
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $error = 'Failed to book appointment. Please try again.';
                    }
                }
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
                        <li><a href="../doctors.php">Our Doctors</a></li>
                        <li><a href="dashboard.php">My Dashboard</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container" style="padding: 60px 20px;">
        <div class="card" style="max-width: 700px; margin: 0 auto;">
            <h2 style="margin-bottom: 30px; color: var(--primary-color);">Book Appointment</h2>
            
            <div style="background-color: var(--light-gray); padding: 20px; border-radius: 5px; margin-bottom: 30px;">
                <h3 style="margin-bottom: 15px;">Doctor Details</h3>
                <p><strong>Name:</strong> Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></p>
                <p><strong>Specialization:</strong> <?php echo htmlspecialchars($doctor['specialization_name'] ?? 'General Medicine'); ?></p>
                <p><strong>Qualification:</strong> <?php echo htmlspecialchars($doctor['qualification']); ?></p>
                <p><strong>Experience:</strong> <?php echo $doctor['experience_years']; ?> years</p>
                <p><strong>Consultation Fee:</strong> Rs. <?php echo number_format($doctor['consultation_fee'], 2); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="book-appointment.php">
                <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                
                <div class="form-group">
                    <label for="appointment_date">Appointment Date *</label>
                    <input type="date" id="appointment_date" name="appointment_date" class="form-control" required 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="appointment_time">Appointment Time *</label>
                    <select id="appointment_time" name="appointment_time" class="form-control" required>
                        <option value="">Select Time</option>
                        <option value="09:00:00">09:00 AM</option>
                        <option value="09:30:00">09:30 AM</option>
                        <option value="10:00:00">10:00 AM</option>
                        <option value="10:30:00">10:30 AM</option>
                        <option value="11:00:00">11:00 AM</option>
                        <option value="11:30:00">11:30 AM</option>
                        <option value="12:00:00">12:00 PM</option>
                        <option value="14:00:00">02:00 PM</option>
                        <option value="14:30:00">02:30 PM</option>
                        <option value="15:00:00">03:00 PM</option>
                        <option value="15:30:00">03:30 PM</option>
                        <option value="16:00:00">04:00 PM</option>
                        <option value="16:30:00">04:30 PM</option>
                        <option value="17:00:00">05:00 PM</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="reason">Reason for Visit</label>
                    <textarea id="reason" name="reason" class="form-control" rows="4" 
                              placeholder="Please describe your symptoms or reason for consultation..."><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                </div>
                
                <div class="alert alert-info">
                    <strong>Note:</strong> Your appointment will be pending until confirmed by the doctor or admin. You will be notified once confirmed.
                </div>
                
                <div class="d-flex gap-10">
                    <button type="submit" class="btn btn-primary">Book Appointment</button>
                    <a href="../doctors.php" class="btn btn-danger">Cancel</a>
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
