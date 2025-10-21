<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('admin');
    
    $conn = getDBConnection();
    
    // Get statistics
    $stats = [];
    
    // Total patients
    $result = $conn->query("SELECT COUNT(*) as count FROM patients");
    $stats['patients'] = $result->fetch_assoc()['count'];
    
    // Total doctors
    $result = $conn->query("SELECT COUNT(*) as count FROM doctors");
    $stats['doctors'] = $result->fetch_assoc()['count'];
    
    // Total appointments
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments");
    $stats['appointments'] = $result->fetch_assoc()['count'];
    
    // Pending appointments
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
    $stats['pending'] = $result->fetch_assoc()['count'];
    
    // Recent appointments
    $recent_appointments = $conn->query("
        SELECT a.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name,
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               s.name as specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    
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
                        <li><a href="doctors.php">Manage Doctors</a></li>
                        <li><a href="patients.php">Manage Patients</a></li>
                        <li><a href="appointments.php">Appointments</a></li>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="reset-password.php">Reset Password</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            </div>

            <div class="dashboard-grid">
                <div class="stat-card" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                    <h3><?php echo $stats['patients']; ?></h3>
                    <p>Total Patients</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">
                    <h3><?php echo $stats['doctors']; ?></h3>
                    <p>Total Doctors</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                    <h3><?php echo $stats['appointments']; ?></h3>
                    <p>Total Appointments</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>Pending Appointments</p>
                </div>
            </div>

            <div class="card">
                <h2>Quick Actions</h2>
                <div class="d-flex gap-10" style="flex-wrap: wrap; margin-top: 20px;">
                    <a href="add-doctor.php" class="btn btn-primary">Add New Doctor</a>
                    <a href="doctors.php" class="btn btn-success">Manage Doctors</a>
                    <a href="patients.php" class="btn btn-primary">View Patients</a>
                    <a href="appointments.php" class="btn btn-warning">View Appointments</a>
                </div>
            </div>

            <div class="card">
                <h2>Recent Appointments</h2>
                <?php if ($recent_appointments->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $recent_appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $appointment['appointment_id']; ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
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
