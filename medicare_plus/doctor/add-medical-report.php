<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medical Report - Doctor Dashboard</title>
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
    
    // Get pre-selected patient ID from URL if provided
    $preselected_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
    
    // Get all patients who have had appointments with this doctor
    $patients = $conn->query("
        SELECT DISTINCT 
            p.patient_id,
            CONCAT(p.first_name, ' ', p.last_name) as patient_name,
            p.blood_group,
            u.email
        FROM patients p
        JOIN appointments a ON p.patient_id = a.patient_id
        JOIN users u ON p.user_id = u.user_id
        WHERE a.doctor_id = $doctor_id
        ORDER BY patient_name ASC
    ");
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $appointment_id = !empty($_POST['appointment_id']) ? intval($_POST['appointment_id']) : null;
        $report_type = sanitizeInput($_POST['report_type'] ?? '');
        $report_title = sanitizeInput($_POST['report_title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if ($patient_id === 0 || empty($report_title) || empty($report_type)) {
            $error = 'Please fill in all required fields.';
        } else {
            // Handle file upload if provided
            $file_path = null;
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
                    $file_name = 'report_' . $patient_id . '_' . time() . '.' . $file_extension;
                    $file_path = $upload_dir . $file_name;
                    
                    if (!move_uploaded_file($_FILES['report_file']['tmp_name'], $file_path)) {
                        $error = 'Failed to upload file.';
                    }
                }
            }
            
            if (empty($error)) {
                // Insert medical report
                $stmt = $conn->prepare("
                    INSERT INTO medical_reports 
                    (patient_id, doctor_id, appointment_id, report_type, report_title, description, file_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iiissss", $patient_id, $doctor_id, $appointment_id, $report_type, $report_title, $description, $file_path);
                
                if ($stmt->execute()) {
                    setSuccessMessage('Medical report added successfully!');
                    header('Location: medical-reports.php');
                    exit();
                } else {
                    $error = 'Failed to save medical report. Please try again.';
                }
                $stmt->close();
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
                <h1>Add New Medical Report</h1>
                <p>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="card">
                <h2>📋 Medical Report Information</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label for="patient_id">Select Patient *</label>
                            <select id="patient_id" name="patient_id" class="form-control" required onchange="loadAppointments(this.value)">
                                <option value="">-- Select Patient --</option>
                                <?php while ($patient = $patients->fetch_assoc()): ?>
                                    <option value="<?php echo $patient['patient_id']; ?>" 
                                            data-email="<?php echo htmlspecialchars($patient['email']); ?>"
                                            data-blood="<?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?>"
                                            <?php echo ($patient['patient_id'] == $preselected_patient_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['patient_name']); ?> 
                                        (ID: <?php echo $patient['patient_id']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small id="patientInfo" style="color: var(--dark-gray); display: block; margin-top: 5px;"></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="appointment_id">Related Appointment (Optional)</label>
                            <select id="appointment_id" name="appointment_id" class="form-control">
                                <option value="">-- Select Appointment --</option>
                            </select>
                            <small style="color: var(--dark-gray); display: block; margin-top: 5px;">
                                Link this report to a specific appointment
                            </small>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label for="report_type">Report Type *</label>
                            <select id="report_type" name="report_type" class="form-control" required>
                                <option value="">-- Select Type --</option>
                                <option value="General Checkup">General Checkup</option>
                                <option value="Blood Test">Blood Test</option>
                                <option value="X-Ray">X-Ray</option>
                                <option value="MRI">MRI</option>
                                <option value="CT Scan">CT Scan</option>
                                <option value="Ultrasound">Ultrasound</option>
                                <option value="ECG">ECG</option>
                                <option value="Lab Report">Lab Report</option>
                                <option value="Prescription">Prescription</option>
                                <option value="Diagnosis">Diagnosis</option>
                                <option value="Follow-up">Follow-up Report</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="report_title">Report Title *</label>
                            <input type="text" id="report_title" name="report_title" class="form-control" required 
                                   placeholder="e.g., Complete Blood Count Results">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Report Description/Notes *</label>
                        <textarea id="description" name="description" class="form-control" rows="8" required 
                                  placeholder="Enter detailed report description, findings, recommendations, and any important notes..."></textarea>
                        <small style="color: var(--dark-gray); display: block; margin-top: 5px;">
                            Provide comprehensive details about the medical report
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="report_file">Attach Report File (Optional)</label>
                        <input type="file" id="report_file" name="report_file" class="form-control" 
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small style="color: var(--dark-gray); display: block; margin-top: 5px;">
                            Allowed formats: PDF, JPG, PNG, DOC, DOCX (Max size: 5MB)
                        </small>
                    </div>
                    
                    <div style="background-color: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <strong>📌 Note:</strong>
                        <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                            <li>All medical reports are securely stored and accessible only to you and the patient</li>
                            <li>Patients will be able to view this report from their dashboard</li>
                            <li>You can edit or update the report later if needed</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex gap-10">
                        <button type="submit" class="btn btn-success">💾 Save Medical Report</button>
                        <a href="medical-reports.php" class="btn btn-danger">Cancel</a>
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
    <script>
        // Show patient info when selected
        document.getElementById('patient_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const patientInfo = document.getElementById('patientInfo');
            
            if (this.value) {
                const email = selectedOption.getAttribute('data-email');
                const blood = selectedOption.getAttribute('data-blood');
                patientInfo.textContent = `Email: ${email} | Blood Group: ${blood}`;
            } else {
                patientInfo.textContent = '';
            }
        });
        
        // Trigger change event if patient is pre-selected
        if (document.getElementById('patient_id').value) {
            document.getElementById('patient_id').dispatchEvent(new Event('change'));
            loadAppointments(document.getElementById('patient_id').value);
        }
        
        // Load appointments for selected patient
        function loadAppointments(patientId) {
            const appointmentSelect = document.getElementById('appointment_id');
            appointmentSelect.innerHTML = '<option value="">Loading...</option>';
            
            if (!patientId) {
                appointmentSelect.innerHTML = '<option value="">-- Select Appointment --</option>';
                return;
            }
            
            // Fetch appointments via AJAX
            fetch(`get-patient-appointments.php?patient_id=${patientId}&doctor_id=<?php echo $doctor_id; ?>`)
                .then(response => response.json())
                .then(data => {
                    appointmentSelect.innerHTML = '<option value="">-- No Appointment --</option>';
                    
                    data.forEach(appointment => {
                        const option = document.createElement('option');
                        option.value = appointment.appointment_id;
                        option.textContent = `${appointment.appointment_date} - ${appointment.appointment_time} (${appointment.status})`;
                        appointmentSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    appointmentSelect.innerHTML = '<option value="">-- Select Appointment --</option>';
                    console.error('Error loading appointments:', error);
                });
        }
    </script>
</body>
</html>
