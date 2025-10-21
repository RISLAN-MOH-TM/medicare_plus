<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Medical Report - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
        header('Location: ../logout.php');
        exit();
    }
    
    $patient = $patient_result->fetch_assoc();
    $patient_id = $patient['patient_id'];
    
    // Get report ID from URL
    $report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($report_id === 0) {
        setErrorMessage('Invalid report ID.');
        header('Location: reports.php');
        exit();
    }
    
    // Get report details
    $stmt = $conn->prepare("
        SELECT r.*, 
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               s.name as specialization,
               d.qualification,
               a.appointment_date
        FROM medical_reports r
        LEFT JOIN doctors d ON r.doctor_id = d.doctor_id
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        LEFT JOIN appointments a ON r.appointment_id = a.appointment_id
        WHERE r.report_id = ? AND r.patient_id = ?
    ");
    $stmt->bind_param("ii", $report_id, $patient_id);
    $stmt->execute();
    $report_result = $stmt->get_result();
    
    if ($report_result->num_rows === 0) {
        setErrorMessage('Report not found or you do not have permission to view it.');
        header('Location: reports.php');
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
                <h1>Medical Report Details</h1>
                <p>Report ID: #<?php echo $report_id; ?></p>
            </div>

            <div class="card">
                <div class="d-flex justify-between align-center" style="margin-bottom: 20px;">
                    <h2><?php echo htmlspecialchars($report['report_title']); ?></h2>
                    <div class="d-flex gap-10">
                        <a href="reports.php" class="btn btn-primary">Back to Reports</a>
                        <button onclick="window.print()" class="btn btn-success">🖨️ Print</button>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 8px;">
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
                    
                    <?php if ($report['doctor_name']): ?>
                        <div>
                            <p><strong>Attending Doctor:</strong></p>
                            <p style="margin-top: 5px;"><?php echo htmlspecialchars($report['doctor_name']); ?></p>
                            <?php if ($report['specialization']): ?>
                                <p style="color: var(--dark-gray); font-size: 0.9rem;"><?php echo htmlspecialchars($report['specialization']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($report['appointment_date']): ?>
                        <div>
                            <p><strong>Appointment Date:</strong></p>
                            <p style="margin-top: 5px;"><?php echo date('F d, Y', strtotime($report['appointment_date'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="border-top: 2px solid #e0e0e0; padding-top: 20px;">
                    <h3 style="color: var(--primary-color); margin-bottom: 15px;">Report Description</h3>
                    <div style="background-color: white; padding: 20px; border-left: 4px solid var(--secondary-color); border-radius: 4px;">
                        <?php if ($report['description']): ?>
                            <p style="line-height: 1.8; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                        <?php else: ?>
                            <p style="color: var(--dark-gray); font-style: italic;">No description provided.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($report['file_path']): ?>
                    <div style="border-top: 2px solid #e0e0e0; padding-top: 20px; margin-top: 20px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 15px;">Attached Files</h3>
                        <div style="background-color: #f0f8ff; padding: 20px; border-radius: 8px; text-align: center;">
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
                
                <div style="border-top: 2px solid #e0e0e0; padding-top: 20px; margin-top: 30px;">
                    <div class="d-flex gap-10" style="flex-wrap: wrap;">
                        <a href="reports.php" class="btn btn-primary">← Back to All Reports</a>
                        <a href="dashboard.php" class="btn btn-success">Go to Dashboard</a>
                        <?php if ($report['doctor_id']): ?>
                            <a href="../doctor-profile.php?id=<?php echo $report['doctor_id']; ?>" class="btn btn-primary">View Doctor Profile</a>
                        <?php endif; ?>
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
