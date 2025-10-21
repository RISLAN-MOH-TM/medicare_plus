<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('admin');
    
    $success = getSuccessMessage();
    $error = getErrorMessage();
    
    // Handle activate/deactivate
    if (isset($_GET['toggle_status'])) {
        $patient_id = intval($_GET['toggle_status']);
        $conn = getDBConnection();
        
        // Get user_id and current status
        $stmt = $conn->prepare("SELECT u.user_id, u.is_active FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $user_id = $data['user_id'];
            $new_status = $data['is_active'] ? 0 : 1;
            
            // Update status
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $new_status, $user_id);
            
            if ($stmt->execute()) {
                setSuccessMessage('Patient status updated successfully!');
            } else {
                setErrorMessage('Failed to update patient status.');
            }
        }
        
        $stmt->close();
        closeDBConnection($conn);
        header('Location: patients.php');
        exit();
    }
    
    $conn = getDBConnection();
    
    // Get all patients
    $patients = $conn->query("
        SELECT p.*, u.email, u.username, u.is_active,
               COUNT(DISTINCT a.appointment_id) as total_appointments
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        LEFT JOIN appointments a ON p.patient_id = a.patient_id
        GROUP BY p.patient_id
        ORDER BY p.patient_id DESC
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
                        <li><a href="reset-password.php">Reset Password</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="container">
            <h1>Manage Patients</h1>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

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
                                    <th>Date of Birth</th>
                                    <th>Gender</th>
                                    <th>Blood Group</th>
                                    <th>Appointments</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($patient = $patients->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $patient['patient_id']; ?></td>
                                        <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo $patient['date_of_birth'] ? date('M d, Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($patient['gender'] ?? 'N/A')); ?></td>
                                        <td><?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?></td>
                                        <td><?php echo $patient['total_appointments']; ?></td>
                                        <td>
                                            <span style="padding: 5px 10px; border-radius: 3px; font-size: 0.85rem; font-weight: 600;
                                                background-color: <?php echo $patient['is_active'] ? '#27ae60' : '#e74c3c'; ?>; color: white;">
                                                <?php echo $patient['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit-patient.php?id=<?php echo $patient['patient_id']; ?>" 
                                               class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85rem;">Edit</a>
                                            <?php if ($patient['is_active']): ?>
                                                <a href="patients.php?toggle_status=<?php echo $patient['patient_id']; ?>" 
                                                   class="btn btn-warning" style="padding: 5px 10px; font-size: 0.85rem;"
                                                   onclick="return confirm('Are you sure you want to deactivate this patient?');">Deactivate</a>
                                            <?php else: ?>
                                                <a href="patients.php?toggle_status=<?php echo $patient['patient_id']; ?>" 
                                                   class="btn btn-success" style="padding: 5px 10px; font-size: 0.85rem;"
                                                   onclick="return confirm('Are you sure you want to activate this patient?');">Activate</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">No patients found.</p>
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
