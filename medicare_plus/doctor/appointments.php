<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments - Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('doctor');
    
    $conn = getDBConnection();
    $user_id = getCurrentUserId();
    
    // Get doctor info
    $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $doctor_result = $stmt->get_result();
    
    if ($doctor_result->num_rows === 0) {
        setErrorMessage('Doctor profile not found.');
        header('Location: ../logout.php');
        exit();
    }
    
    $doctor_id = $doctor_result->fetch_assoc()['doctor_id'];
    
    $success = getSuccessMessage();
    $error = getErrorMessage();
    
    // Handle status update
    if (isset($_POST['update_status'])) {
        $appointment_id = intval($_POST['appointment_id']);
        $new_status = sanitizeInput($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
        $stmt->bind_param("sii", $new_status, $appointment_id, $doctor_id);
        if ($stmt->execute()) {
            setSuccessMessage('Appointment status updated successfully.');
            header('Location: appointments.php');
            exit();
        }
    }
    
    // Get filter
    $status_filter = sanitizeInput($_GET['status'] ?? '');
    
    // Build query
    $query = "
        SELECT a.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name,
               p.phone, p.blood_group, p.date_of_birth
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = $doctor_id
    ";
    
    if (!empty($status_filter)) {
        $query .= " AND a.status = '$status_filter'";
    }
    
    $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    
    $appointments = $conn->query($query);
    
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
            <h1>All Appointments</h1>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <form method="GET" action="appointments.php" class="d-flex gap-10" style="margin-bottom: 20px;">
                    <select name="status" class="form-control" style="max-width: 200px;">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="appointments.php" class="btn btn-danger">Clear</a>
                </form>
                
                <?php if ($appointments->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Phone</th>
                                    <th>Blood Group</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $appointment['appointment_id']; ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['blood_group'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td><?php echo htmlspecialchars(substr($appointment['reason'] ?? 'Not specified', 0, 30)); ?></td>
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
                                            <?php if ($appointment['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" name="update_status" class="btn btn-success" 
                                                            style="padding: 5px 10px; font-size: 0.85rem;">Confirm</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($appointment['status'] === 'confirmed'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" name="update_status" class="btn btn-primary" 
                                                            style="padding: 5px 10px; font-size: 0.85rem;">Complete</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">No appointments found.</p>
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
