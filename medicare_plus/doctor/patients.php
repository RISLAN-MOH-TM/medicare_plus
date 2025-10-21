<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Patients - Doctor Dashboard</title>
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
    
    // Get all unique patients who have had appointments
    $patients = $conn->query("
        SELECT DISTINCT 
            p.patient_id,
            p.first_name,
            p.last_name,
            p.phone,
            p.date_of_birth,
            p.gender,
            p.blood_group,
            p.address,
            u.email,
            COUNT(DISTINCT a.appointment_id) as total_appointments,
            MAX(a.appointment_date) as last_visit
        FROM patients p
        JOIN appointments a ON p.patient_id = a.patient_id
        JOIN users u ON p.user_id = u.user_id
        WHERE a.doctor_id = $doctor_id
        GROUP BY p.patient_id
        ORDER BY last_visit DESC
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
            <h1>My Patients</h1>

            <div class="card">
                <div class="form-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search patients by name, email, or phone...">
                </div>
                
                <?php if ($patients->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table id="patientsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Gender</th>
                                    <th>Blood Group</th>
                                    <th>Total Visits</th>
                                    <th>Last Visit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($patient = $patients->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $patient['patient_id']; ?></td>
                                        <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($patient['gender'] ?? 'N/A')); ?></td>
                                        <td><?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?></td>
                                        <td><?php echo $patient['total_appointments']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($patient['last_visit'])); ?></td>
                                        <td>
                                            <a href="patient-medical-reports.php?patient_id=<?php echo $patient['patient_id']; ?>" 
                                               class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85rem;">View Reports</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">
                        No patients found. Patients will appear here after they book appointments with you.
                    </p>
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
        searchTable('searchInput', 'patientsTable');
    </script>
</body>
</html>
