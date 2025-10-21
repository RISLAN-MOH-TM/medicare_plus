<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medical Report - Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('doctor');
    
    $conn = getDBConnection();
    $user_id = getCurrentUserId();
    $error = '';
    $success = '';
    
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
               p.patient_id
        FROM medical_reports r
        JOIN patients p ON r.patient_id = p.patient_id
        WHERE r.report_id = ? AND r.doctor_id = ?
    ");
    $stmt->bind_param("ii", $report_id, $doctor_id);
    $stmt->execute();
    $report_result = $stmt->get_result();
    
    if ($report_result->num_rows === 0) {
        setErrorMessage('Report not found or you do not have permission to edit it.');
        header('Location: medical-reports.php');
        exit();
    }
    
    $report = $report_result->fetch_assoc();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_report'])) {
            $report_type = sanitizeInput($_POST['report_type'] ?? '');
            $report_title = sanitizeInput($_POST['report_title'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $appointment_id = !empty($_POST['appointment_id']) ? intval($_POST['appointment_id']) : null;
            
            if (empty($report_title) || empty($report_type)) {
                $error = 'Please fill in all required fields.';
            } else {
                // Handle file upload if provided
                $file_path = $report['file_path'];
                if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/medical_reports/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION);
                    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                    
                    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                        $error = 'Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX';
                    } else {
                        // Delete old file if exists
                        if ($file_path && file_exists($file_path)) {
                            unlink($file_path);
                        }
                        
                        $file_name = 'report_' . $report['patient_id'] . '_' . time() . '.' . $file_extension;
                        $file_path = $upload_dir . $file_name;
                        
                        if (!move_uploaded_file($_FILES['report_file']['tmp_name'], $file_path)) {
                            $error = 'Failed to upload file.';
                        }
                    }
                }
                
                if (empty($error)) {
                    // Update medical report
                    $stmt = $conn->prepare("
                        UPDATE medical_reports 
                        SET report_type = ?, report_title = ?, description = ?, appointment_id = ?, file_path = ?
                        WHERE report_id = ? AND doctor_id = ?
                    ");
                    $stmt->bind_param("sssisii", $report_type, $report_title, $description, $appointment_id, $file_path, $report_id, $doctor_id);
                    
                    if ($stmt->execute()) {
                        $success = 'Medical report updated successfully!';
                        // Refresh report data
                        $report['report_type'] = $report_type;
                        $report['report_title'] = $report_title;
                        $report['description'] = $description;
                        $report['appointment_id'] = $appointment_id;
                        $report['file_path'] = $file_path;
                    } else {
                        $error = 'Failed to update medical report. Please try again.';
                    }
                    $stmt->close();
                }
            }
        } elseif (isset($_POST['delete_report'])) {
            // Delete the report
            $stmt = $conn->prepare("DELETE FROM medical_reports WHERE report_id = ? AND doctor_id = ?");
            $stmt->bind_param("ii", $report_id, $doctor_id);
            
            if ($stmt->execute()) {
                // Delete file if exists
                if ($report['file_path'] && file_exists($report['file_path'])) {
                    unlink($report['file_path']);
                }
                
                setSuccessMessage('Medical report deleted successfully!');
                header('Location: medical-reports.php');
                exit();
            } else {
                $error = 'Failed to delete report. Please try again.';
            }
            $stmt->close();
        }
    }
    
    // Get appointments for this patient
    $appointments = $conn->query("
        SELECT appointment_id, appointment_date, appointment_time, status
        FROM appointments
        WHERE patient_id = " . $report['patient_id'] . " AND doctor_id = $doctor_id
        ORDER BY appointment_date DESC, appointment_time DESC
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
                <h1>Edit Medical Report</h1>
                <p>Report ID: #<?php echo $report_id; ?> | Patient: <?php echo htmlspecialchars($report['patient_name']); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="card">
                <h2>📋 Edit Report Information</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label for="patient_name">Patient Name</label>
                            <input type="text" id="patient_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($report['patient_name']); ?>" disabled>
                            <small style="color: var(--dark-gray); display: block; margin-top: 5px;">
                                Patient cannot be changed
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="appointment_id">Related Appointment (Optional)</label>
                            <select id="appointment_id" name="appointment_id" class="form-control">
                                <option value="">-- No Appointment --</option>
                                <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                    <option value="<?php echo $appointment['appointment_id']; ?>"
                                            <?php echo ($report['appointment_id'] == $appointment['appointment_id']) ? 'selected' : ''; ?>>
                                        <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?> - 
                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                        (<?php echo ucfirst($appointment['status']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label for="report_type">Report Type *</label>
                            <select id="report_type" name="report_type" class="form-control" required>
                                <option value="">-- Select Type --</option>
                                <option value="General Checkup" <?php echo ($report['report_type'] === 'General Checkup') ? 'selected' : ''; ?>>General Checkup</option>
                                <option value="Blood Test" <?php echo ($report['report_type'] === 'Blood Test') ? 'selected' : ''; ?>>Blood Test</option>
                                <option value="X-Ray" <?php echo ($report['report_type'] === 'X-Ray') ? 'selected' : ''; ?>>X-Ray</option>
                                <option value="MRI" <?php echo ($report['report_type'] === 'MRI') ? 'selected' : ''; ?>>MRI</option>
                                <option value="CT Scan" <?php echo ($report['report_type'] === 'CT Scan') ? 'selected' : ''; ?>>CT Scan</option>
                                <option value="Ultrasound" <?php echo ($report['report_type'] === 'Ultrasound') ? 'selected' : ''; ?>>Ultrasound</option>
                                <option value="ECG" <?php echo ($report['report_type'] === 'ECG') ? 'selected' : ''; ?>>ECG</option>
                                <option value="Lab Report" <?php echo ($report['report_type'] === 'Lab Report') ? 'selected' : ''; ?>>Lab Report</option>
                                <option value="Prescription" <?php echo ($report['report_type'] === 'Prescription') ? 'selected' : ''; ?>>Prescription</option>
                                <option value="Diagnosis" <?php echo ($report['report_type'] === 'Diagnosis') ? 'selected' : ''; ?>>Diagnosis</option>
                                <option value="Follow-up" <?php echo ($report['report_type'] === 'Follow-up') ? 'selected' : ''; ?>>Follow-up Report</option>
                                <option value="Other" <?php echo ($report['report_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="report_title">Report Title *</label>
                            <input type="text" id="report_title" name="report_title" class="form-control" required 
                                   value="<?php echo htmlspecialchars($report['report_title']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Report Description/Notes *</label>
                        <textarea id="description" name="description" class="form-control" rows="8" required><?php echo htmlspecialchars($report['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="report_file">Update Report File (Optional)</label>
                        <?php if ($report['file_path']): ?>
                            <p style="margin-bottom: 10px;">
                                <strong>Current File:</strong> 
                                <a href="<?php echo htmlspecialchars($report['file_path']); ?>" target="_blank" style="color: var(--secondary-color);">
                                    <?php echo htmlspecialchars(basename($report['file_path'])); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <input type="file" id="report_file" name="report_file" class="form-control" 
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small style="color: var(--dark-gray); display: block; margin-top: 5px;">
                            Upload a new file to replace the existing one. Allowed formats: PDF, JPG, PNG, DOC, DOCX
                        </small>
                    </div>
                    
                    <div class="d-flex gap-10" style="flex-wrap: wrap;">
                        <button type="submit" name="update_report" class="btn btn-success">💾 Update Medical Report</button>
                        <a href="view-medical-report.php?id=<?php echo $report_id; ?>" class="btn btn-primary">View Report</a>
                        <a href="medical-reports.php" class="btn btn-primary">Back to Reports</a>
                        <button type="submit" name="delete_report" class="btn btn-danger" 
                                onclick="return confirm('Are you sure you want to delete this medical report? This action cannot be undone.');">
                            🗑️ Delete Report
                        </button>
                    </div>
                </form>
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
