<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Reports - MediCare Plus</title>
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
    
    // Get all medical reports
    $reports = $conn->query("
        SELECT r.*, 
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               s.name as specialization
        FROM medical_reports r
        LEFT JOIN doctors d ON r.doctor_id = d.doctor_id
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
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
                <h1>My Medical Reports</h1>
                <p>Patient: <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
            </div>

            <div class="card">
                <div class="d-flex justify-between align-center" style="margin-bottom: 20px;">
                    <h2>All Medical Reports</h2>
                    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
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
                                        <td><?php echo date('M d, Y h:i A', strtotime($report['upload_date'])); ?></td>
                                        <td>
                                            <a href="view-report.php?id=<?php echo $report['report_id']; ?>" 
                                               class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85rem;">View Details</a>
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
                            You don't have any medical reports yet. Reports will appear here after your doctor uploads them.
                        </p>
                        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
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
