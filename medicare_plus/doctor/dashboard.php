<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('doctor');
    
    $conn = getDBConnection();
    $user_id = getCurrentUserId();
    
    // Get doctor info
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $doctor_result = $stmt->get_result();
    
    if ($doctor_result->num_rows === 0) {
        setErrorMessage('Doctor profile not found.');
        header('Location: ../logout.php');
        exit();
    }
    
    $doctor = $doctor_result->fetch_assoc();
    $doctor_id = $doctor['doctor_id'];
    
    $success = getSuccessMessage();
    $error = getErrorMessage();
    
    // Handle appointment status update
    if (isset($_POST['update_status'])) {
        $appointment_id = intval($_POST['appointment_id']);
        $new_status = sanitizeInput($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
        $stmt->bind_param("sii", $new_status, $appointment_id, $doctor_id);
        if ($stmt->execute()) {
            setSuccessMessage('Appointment status updated successfully.');
            header('Location: dashboard.php');
            exit();
        }
    }
    
    // Get appointments
    $today = date('Y-m-d');
    $today_appointments = $conn->query("
        SELECT a.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name,
               p.phone, p.blood_group
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = $doctor_id AND a.appointment_date = '$today'
        ORDER BY a.appointment_time ASC
    ");
    
    $upcoming_appointments = $conn->query("
        SELECT a.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name,
               p.phone
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = $doctor_id AND a.appointment_date > '$today'
        AND a.status != 'cancelled'
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 10
    ");
    
    // Get statistics
    $stats = [];
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = $doctor_id AND appointment_date = '$today'");
    $stats['today'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = $doctor_id AND status = 'pending'");
    $stats['pending'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = $doctor_id AND status = 'completed'");
    $stats['completed'] = $result->fetch_assoc()['count'];
    
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
                        <li><a href="appointments.php">All Appointments</a></li>
                        <li><a href="patients.php">My Patients</a></li>
                        <li><a href="medical-reports.php">Medical Reports</a></li>
                        <li><a href="messages.php">Messages</a></li>
                        <li><a href="profile.php">My Profile</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>Welcome, Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>!</h1>
                <p><?php echo date('l, F d, Y'); ?></p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <div class="stat-card" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                    <h3><?php echo $stats['today']; ?></h3>
                    <p>Today's Appointments</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>Pending Appointments</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">
                    <h3><?php echo $stats['completed']; ?></h3>
                    <p>Completed Consultations</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">
                    <h3><?php echo number_format($doctor['rating'], 1); ?> ⭐</h3>
                    <p>Your Rating (<?php echo $doctor['total_reviews']; ?> reviews)</p>
                </div>
            </div>

            <div class="card">
                <h2>Today's Appointments (<?php echo date('M d, Y'); ?>)</h2>
                <?php if ($today_appointments->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient Name</th>
                                    <th>Phone</th>
                                    <th>Blood Group</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $today_appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['blood_group'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(substr($appointment['reason'] ?? 'Not specified', 0, 50)); ?></td>
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
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                <?php if ($appointment['status'] === 'pending'): ?>
                                                    <button type="submit" name="update_status" value="confirmed" class="btn btn-success" 
                                                            style="padding: 5px 10px; font-size: 0.85rem;">Confirm</button>
                                                <?php endif; ?>
                                                <?php if ($appointment['status'] === 'confirmed'): ?>
                                                    <button type="submit" name="update_status" value="completed" class="btn btn-primary" 
                                                            style="padding: 5px 10px; font-size: 0.85rem;">Complete</button>
                                                <?php endif; ?>
                                            </form>
                                            <a href="patient-details.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" 
                                               class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85rem;">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">No appointments scheduled for today.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Upcoming Appointments</h2>
                <?php if ($upcoming_appointments->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Patient Name</th>
                                    <th>Phone</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $upcoming_appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(substr($appointment['reason'] ?? 'Not specified', 0, 40)); ?></td>
                                        <td>
                                            <span style="padding: 5px 10px; border-radius: 3px; font-size: 0.85rem; font-weight: 600;
                                                background-color: <?php 
                                                    echo $appointment['status'] === 'pending' ? '#f39c12' : '#3498db'; 
                                                ?>; color: white;">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($appointment['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" name="update_status" class="btn btn-success" 
                                                            style="padding: 5px 10px; font-size: 0.85rem;">Confirm</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">No upcoming appointments.</p>
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
