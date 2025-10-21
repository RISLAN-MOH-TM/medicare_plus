<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Medical Reports - Doctor Dashboard</title>
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
    
    // Get patient ID from URL
    $patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
    
    if ($patient_id === 0) {
        setErrorMessage('Invalid patient ID.');
        header('Location: patients.php');
        exit();
    }
    
    // Get patient details
    $stmt = $conn->prepare("
        SELECT p.*, u.email
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.patient_id = ?
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient_result = $stmt->get_result();
    
    if ($patient_result->num_rows === 0) {
        setErrorMessage('Patient not found.');
        header('Location: patients.php');
        exit();
    }
    
    $patient = $patient_result->fetch_assoc();
    
    // Get all medical reports for this patient (from all doctors)
    $reports = $conn->query("
        SELECT r.*, 
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               s.name as specialization,
               a.appointment_date
        FROM medical_reports r
        LEFT JOIN doctors d ON r.doctor_id = d.doctor_id
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        LEFT JOIN appointments a ON r.appointment_id = a.appointment_id
        WHERE r.patient_id = $patient_id
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
                <h1>Medical Reports for <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h1>
                <p>Patient ID: #<?php echo $patient_id; ?></p>
            </div>

            <!-- Patient Information Card -->
            <div class="card">
                <h2>👤 Patient Information</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                    <div>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                    </div>
                    <div>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p><strong>Date of Birth:</strong> <?php echo $patient['date_of_birth'] ? date('M d, Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?></p>
                    </div>
                    <div>
                        <p><strong>Gender:</strong> <?php echo htmlspecialchars(ucfirst($patient['gender'] ?? 'N/A')); ?></p>
                    </div>
                    <div>
                        <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Medical Reports Card -->
            <div class="card">
                <div class="d-flex justify-between align-center" style="margin-bottom: 20px;">
                    <h2>All Medical Reports</h2>
                    <div class="d-flex gap-10">
                        <a href="add-medical-report.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-success">➕ Add New Report</a>
                        <a href="patients.php" class="btn btn-primary">Back to Patients</a>
                    </div>
                </div>
                
                <?php if ($reports->num_rows > 0): ?>
                    <div class="form-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search reports by title, type, or doctor...">
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table id="reportsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Report Title</th>
                                    <th>Type</th>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Appointment Date</th>
                                    <th>Upload Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($report = $reports->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $report['report_id']; ?></td>
                                        <td><?php echo htmlspecialchars($report['report_title']); ?></td>
                                        <td>
                                            <span style="padding: 5px 10px; border-radius: 3px; font-size: 0.85rem; font-weight: 600;
                                                background-color: #3498db; color: white;">
                                                <?php echo htmlspecialchars($report['report_type'] ?? 'General'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($report['doctor_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($report['specialization'] ?? 'N/A'); ?></td>
                                        <td><?php echo $report['appointment_date'] ? date('M d, Y', strtotime($report['appointment_date'])) : 'N/A'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($report['upload_date'])); ?></td>
                                        <td>
                                            <?php if ($report['doctor_id'] == $doctor_id): ?>
                                                <a href="view-medical-report.php?id=<?php echo $report['report_id']; ?>" 
                                                   class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85rem;">View/Edit</a>
                                            <?php else: ?>
                                                <span style="padding: 5px 10px; border-radius: 3px; font-size: 0.85rem;
                                                    background-color: #95a5a6; color: white;">View Only</span>
                                            <?php endif; ?>
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
                            No medical reports have been created for this patient yet.
                        </p>
                        <a href="add-medical-report.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-success">➕ Add First Medical Report</a>
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
