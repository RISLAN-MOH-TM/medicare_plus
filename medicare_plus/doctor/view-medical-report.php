<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Medical Report - Doctor Dashboard</title>
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
    
    // Get report ID from URL
    $report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($report_id === 0) {
        setErrorMessage('Invalid report ID.');
        header('Location: medical-reports.php');
        exit();
    }
    
    // Get report details (only if created by this doctor)
    $stmt = $conn->prepare("
        SELECT r.*, 
               CONCAT(p.first_name, ' ', p.last_name) as patient_name,
               p.phone, u.email, p.blood_group, p.date_of_birth, p.gender,
               a.appointment_date, a.appointment_time
        FROM medical_reports r
        JOIN patients p ON r.patient_id = p.patient_id
        LEFT JOIN users u ON p.user_id = u.user_id
        LEFT JOIN appointments a ON r.appointment_id = a.appointment_id
        WHERE r.report_id = ? AND r.doctor_id = ?
    ");
    $stmt->bind_param("ii", $report_id, $doctor_id);
    $stmt->execute();
    $report_result = $stmt->get_result();
    
    if ($report_result->num_rows === 0) {
        setErrorMessage('Report not found or you do not have permission to view it.');
        header('Location: medical-reports.php');
        exit();
    }
    
    $report = $report_result->fetch_assoc();
    
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
                <h1>Medical Report Details</h1>
                <p>Report ID: #<?php echo $report_id; ?></p>
            </div>

            <div class="card">
                <div class="d-flex justify-between align-center" style="margin-bottom: 20px;">
                    <h2><?php echo htmlspecialchars($report['report_title']); ?></h2>
                    <div class="d-flex gap-10">
                        <a href="edit-medical-report.php?id=<?php echo $report_id; ?>" class="btn btn-warning">✏️ Edit Report</a>
                        <a href="medical-reports.php" class="btn btn-primary">Back to Reports</a>
                        <button onclick="window.print()" class="btn btn-success">🖨️ Print</button>
                    </div>
                </div>
                
                <!-- Patient Information -->
                <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="color: var(--primary-color); margin-bottom: 15px;">👤 Patient Information</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <div>
                            <p><strong>Name:</strong></p>
                            <p style="margin-top: 5px;"><?php echo htmlspecialchars($report['patient_name']); ?></p>
                        </div>
                        <div>
                            <p><strong>Email:</strong></p>
                            <p style="margin-top: 5px;"><?php echo htmlspecialchars($report['email']); ?></p>
                        </div>
                        <div>
                            <p><strong>Phone:</strong></p>
                            <p style="margin-top: 5px;"><?php echo htmlspecialchars($report['phone'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p><strong>Blood Group:</strong></p>
                            <p style="margin-top: 5px;"><?php echo htmlspecialchars($report['blood_group'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p><strong>Gender:</strong></p>
                            <p style="margin-top: 5px;"><?php echo htmlspecialchars(ucfirst($report['gender'] ?? 'N/A')); ?></p>
                        </div>
                        <div>
                            <p><strong>Date of Birth:</strong></p>
                            <p style="margin-top: 5px;"><?php echo $report['date_of_birth'] ? date('M d, Y', strtotime($report['date_of_birth'])) : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Report Information -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; padding: 20px; background-color: #fff3cd; border-radius: 8px;">
                    <div>
                        <p><strong>Report Type:</strong></p>
                        <span style="padding: 5px 15px; border-radius: 3px; font-size: 0.9rem; font-weight: 600;
                            background-color: #3498db; color: white; display: inline-block; margin-top: 5px;">
                            <?php echo htmlspecialchars($report['report_type'] ?? 'General'); ?>
                        </span>
                    </div>
                    
                    <div>
                        <p><strong>Upload Date:</strong></p>
                        <p style="margin-top: 5px;"><?php echo date('F d, Y - h:i A', strtotime($report['upload_date'])); ?></p>
                    </div>
                    
                    <?php if ($report['appointment_date']): ?>
                        <div>
                            <p><strong>Appointment Date:</strong></p>
                            <p style="margin-top: 5px;">
                                <?php echo date('F d, Y', strtotime($report['appointment_date'])); ?>
                                <?php if ($report['appointment_time']): ?>
                                    at <?php echo date('h:i A', strtotime($report['appointment_time'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Report Description -->
                <div style="border-top: 2px solid #e0e0e0; padding-top: 20px;">
                    <h3 style="color: var(--primary-color); margin-bottom: 15px;">📋 Report Description & Findings</h3>
                    <div style="background-color: white; padding: 20px; border-left: 4px solid var(--secondary-color); border-radius: 4px; line-height: 1.8;">
                        <?php if ($report['description']): ?>
                            <p style="white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                        <?php else: ?>
                            <p style="color: var(--dark-gray); font-style: italic;">No description provided.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Attached File -->
                <?php if ($report['file_path']): ?>
                    <div style="border-top: 2px solid #e0e0e0; padding-top: 20px; margin-top: 20px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 15px;">📎 Attached Files</h3>
                        <div style="background-color: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;">
                            <p style="margin-bottom: 15px;">📄 <strong>File:</strong> <?php echo htmlspecialchars(basename($report['file_path'])); ?></p>
                            <a href="<?php echo htmlspecialchars($report['file_path']); ?>" 
                               class="btn btn-success" 
                               download
                               target="_blank">
                                📥 Download Report File
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Actions -->
                <div style="border-top: 2px solid #e0e0e0; padding-top: 20px; margin-top: 30px;">
                    <div class="d-flex gap-10" style="flex-wrap: wrap;">
                        <a href="edit-medical-report.php?id=<?php echo $report_id; ?>" class="btn btn-warning">✏️ Edit This Report</a>
                        <a href="medical-reports.php" class="btn btn-primary">← Back to All Reports</a>
                        <a href="dashboard.php" class="btn btn-success">Go to Dashboard</a>
                    </div>
                </div>
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
