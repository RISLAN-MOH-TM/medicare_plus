<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('patient');
    
    $conn = getDBConnection();
    $user_id = getCurrentUserId();
    
    // Get patient info
    $stmt = $conn->prepare("SELECT * FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $patient_result = $stmt->get_result();
    
    if ($patient_result->num_rows === 0) {
        setErrorMessage('Patient profile not found.');
        header('Location: ../logout.php');
        exit();
    }
    
    $patient = $patient_result->fetch_assoc();
    $patient_id = $patient['patient_id'];
    
    $success = getSuccessMessage();
    $error = getErrorMessage();
    
    // Handle appointment cancellation
    if (isset($_GET['cancel_appointment'])) {
        $appointment_id = intval($_GET['cancel_appointment']);
        $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ? AND patient_id = ?");
        $stmt->bind_param("ii", $appointment_id, $patient_id);
        if ($stmt->execute()) {
            setSuccessMessage('Appointment cancelled successfully.');
        }
        header('Location: dashboard.php');
        exit();
    }
    
    // Get appointments
    $appointments = $conn->query("
        SELECT a.*, 
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               s.name as specialization,
               d.consultation_fee
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        WHERE a.patient_id = $patient_id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 10
    ");
    
    // Get medical reports
    $reports = $conn->query("
        SELECT r.*, 
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name
        FROM medical_reports r
        LEFT JOIN doctors d ON r.doctor_id = d.doctor_id
        WHERE r.patient_id = $patient_id
        ORDER BY r.upload_date DESC
        LIMIT 5
    ");
    
    // Get messages
    $messages = $conn->query("
        SELECT m.*, 
               CONCAT(d.first_name, ' ', d.last_name) as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        JOIN doctors d ON u.user_id = d.user_id
        WHERE m.receiver_id = $user_id
        ORDER BY m.sent_at DESC
        LIMIT 5
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
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../doctors.php">Find Doctors</a></li>
                        <li><a href="dashboard.php">My Dashboard</a></li>
                        <li><a href="messages.php">Messages</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>Welcome, <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>!</h1>
                <p>Patient ID: #<?php echo $patient_id; ?></p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <h2>Quick Actions</h2>
                <div class="d-flex gap-10" style="flex-wrap: wrap; margin-top: 20px;">
                    <a href="../doctors.php" class="btn btn-primary">Book New Appointment</a>
                    <a href="profile.php" class="btn btn-success">Update Profile</a>
                    <a href="messages.php" class="btn btn-primary">View Messages</a>
                    <a href="reports.php" class="btn btn-warning">Medical Reports</a>
                </div>
            </div>

            <div class="card">
                <h2>My Profile Information</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['phone'] ?? 'Not provided'); ?></p>
                        <p><strong>Date of Birth:</strong> <?php echo $patient['date_of_birth'] ? date('M d, Y', strtotime($patient['date_of_birth'])) : 'Not provided'; ?></p>
                    </div>
                    <div>
                        <p><strong>Gender:</strong> <?php echo htmlspecialchars(ucfirst($patient['gender'] ?? 'Not provided')); ?></p>
                        <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient['blood_group'] ?? 'Not provided'); ?></p>
                        <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($patient['emergency_contact'] ?? 'Not provided'); ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="d-flex justify-between align-center" style="margin-bottom: 20px;">
                    <h2>My Appointments</h2>
                    <a href="../doctors.php" class="btn btn-primary">Book New</a>
                </div>
                
                <?php if ($appointments->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Fee</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $appointment['appointment_id']; ?></td>
                                        <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['specialization']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td>
                                            <span style="padding: 5px 10px; border-radius: 3px; font-size: 0.85rem; font-weight: 600;
                                                background-color: <?php 
                                                    echo $appointment['status'] === 'pending' ? '#f39c12' : 
                                                        ($appointment['status'] === 'confirmed' ? '#3498db' : 
                                                        ($appointment['status'] === 'completed' ? '#27ae60' : '#e74c3c')); 
                                                ?>; color: white;">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td>Rs. <?php echo number_format($appointment['consultation_fee'], 2); ?></td>
                                        <td>
                                            <?php if ($appointment['status'] === 'pending' || $appointment['status'] === 'confirmed'): ?>
                                                <a href="dashboard.php?cancel_appointment=<?php echo $appointment['appointment_id']; ?>" 
                                                   class="btn btn-danger" style="padding: 5px 10px; font-size: 0.85rem;"
                                                   onclick="return confirm('Are you sure you want to cancel this appointment?');">Cancel</a>
                                            <?php endif; ?>
                                            <?php if ($appointment['status'] === 'completed'): ?>
                                                <a href="review.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" 
                                                   class="btn btn-success" style="padding: 5px 10px; font-size: 0.85rem;">Review</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">
                        No appointments found. <a href="../doctors.php" style="color: var(--secondary-color);">Book your first appointment</a>
                    </p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Recent Medical Reports</h2>
                <?php if ($reports->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Report Title</th>
                                    <th>Type</th>
                                    <th>Doctor</th>
                                    <th>Upload Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($report = $reports->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($report['report_title']); ?></td>
                                        <td><?php echo htmlspecialchars($report['report_type'] ?? 'General'); ?></td>
                                        <td><?php echo htmlspecialchars($report['doctor_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($report['upload_date'])); ?></td>
                                        <td>
                                            <a href="view-report.php?id=<?php echo $report['report_id']; ?>" 
                                               class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85rem;">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">No medical reports available.</p>
                <?php endif; ?>
            </div>
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
