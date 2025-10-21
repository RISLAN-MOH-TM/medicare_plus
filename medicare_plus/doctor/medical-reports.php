<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Reports - Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('doctor');
    
    $conn = getDBConnection();
    $user_id = getCurrentUserId();
    
    // Get doctor info
    $stmt = $conn->prepare("SELECT doctor_id, first_name, last_name FROM doctors WHERE user_id = ?");
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
    
    // Get all medical reports created by this doctor
    $reports = $conn->query("
        SELECT r.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name,
               p.blood_group,
               a.appointment_date
        FROM medical_reports r
        JOIN patients p ON r.patient_id = p.patient_id
        LEFT JOIN appointments a ON r.appointment_id = a.appointment_id
        WHERE r.doctor_id = $doctor_id
        ORDER BY r.upload_date DESC
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
                <h1>Medical Reports Management</h1>
                <p>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></p>
            </div>

            <div class="card">
                <div class="d-flex justify-between align-center" style="margin-bottom: 20px;">
                    <h2>All Medical Reports</h2>
                    <a href="add-medical-report.php" class="btn btn-success">➕ Add New Report</a>
                </div>
                
                <?php if ($reports->num_rows > 0): ?>
                    <div class="form-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search reports by patient name, title, or type...">
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table id="reportsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient Name</th>
                                    <th>Report Title</th>
                                    <th>Type</th>
                                    <th>Appointment Date</th>
                                    <th>Upload Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($report = $reports->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $report['report_id']; ?></td>
                                        <td><?php echo htmlspecialchars($report['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($report['report_title']); ?></td>
                                        <td>
                                            <span style="padding: 5px 10px; border-radius: 3px; font-size: 0.85rem; font-weight: 600;
                                                background-color: #3498db; color: white;">
                                                <?php echo htmlspecialchars($report['report_type'] ?? 'General'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $report['appointment_date'] ? date('M d, Y', strtotime($report['appointment_date'])) : 'N/A'; ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($report['upload_date'])); ?></td>
                                        <td>
                                            <a href="view-medical-report.php?id=<?php echo $report['report_id']; ?>" 
                                               class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85rem;">View</a>
                                            <a href="edit-medical-report.php?id=<?php echo $report['report_id']; ?>" 
                                               class="btn btn-warning" style="padding: 5px 10px; font-size: 0.85rem;">Edit</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <h3 style="color: var(--dark-gray); margin-bottom: 15px;">📋 No Medical Reports Found</h3>
                        <p style="color: var(--dark-gray); margin-bottom: 25px;">
                            You haven't created any medical reports yet. Click the button below to add your first report.
                        </p>
                        <a href="add-medical-report.php" class="btn btn-success">➕ Add New Medical Report</a>
                    </div>
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
    <script>
        searchTable('searchInput', 'reportsTable');
    </script>
</body>
</html>
