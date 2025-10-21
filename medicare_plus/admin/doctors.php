<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - MediCare Plus</title>
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
        $doctor_id = intval($_GET['toggle_status']);
        $conn = getDBConnection();
        
        // Get user_id and current status
        $stmt = $conn->prepare("SELECT u.user_id, u.is_active FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.doctor_id = ?");
        $stmt->bind_param("i", $doctor_id);
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
                setSuccessMessage('Doctor status updated successfully!');
            } else {
                setErrorMessage('Failed to update doctor status.');
            }
        }
        
        $stmt->close();
        closeDBConnection($conn);
        header('Location: doctors.php');
        exit();
    }
    
    // Handle delete
    if (isset($_GET['delete'])) {
        $doctor_id = intval($_GET['delete']);
        $conn = getDBConnection();
        
        // Get user_id first
        $stmt = $conn->prepare("SELECT user_id FROM doctors WHERE doctor_id = ?");
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_id = $result->fetch_assoc()['user_id'];
            
            // Delete user (will cascade to doctor)
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                setSuccessMessage('Doctor deleted successfully!');
            } else {
                setErrorMessage('Failed to delete doctor.');
            }
        }
        
        $stmt->close();
        closeDBConnection($conn);
        header('Location: doctors.php');
        exit();
    }
    
    // Get all doctors
    $conn = getDBConnection();
    $doctors = $conn->query("
        SELECT d.*, s.name as specialization_name, u.email, u.is_active
        FROM doctors d
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        JOIN users u ON d.user_id = u.user_id
        ORDER BY d.doctor_id DESC
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
            <div class="dashboard-header d-flex justify-between align-center">
                <h1>Manage Doctors</h1>
                <a href="add-doctor.php" class="btn btn-primary">Add New Doctor</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="form-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search doctors by name, specialization, or email...">
                </div>
                
                <?php if ($doctors->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table id="doctorsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Specialization</th>
                                    <th>Qualification</th>
                                    <th>Experience</th>
                                    <th>Fee</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($doctor = $doctors->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $doctor['doctor_id']; ?></td>
                                        <td><?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['specialization_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['qualification']); ?></td>
                                        <td><?php echo $doctor['experience_years'] ?? 0; ?> years</td>
                                        <td>Rs. <?php echo number_format($doctor['consultation_fee'] ?? 0, 2); ?></td>
                                        <td><?php echo number_format($doctor['rating'], 1); ?> ⭐ (<?php echo $doctor['total_reviews']; ?>)</td>
                                        <td>
                                            <span style="padding: 5px 10px; border-radius: 3px; font-size: 0.85rem; font-weight: 600;
                                                background-color: <?php echo $doctor['is_active'] ? '#27ae60' : '#e74c3c'; ?>; color: white;">
                                                <?php echo $doctor['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit-doctor.php?id=<?php echo $doctor['doctor_id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85rem;">Edit</a>
                                            <?php if ($doctor['is_active']): ?>
                                                <a href="doctors.php?toggle_status=<?php echo $doctor['doctor_id']; ?>" 
                                                   class="btn btn-warning" style="padding: 5px 10px; font-size: 0.85rem;"
                                                   onclick="return confirm('Are you sure you want to deactivate this doctor?');">Deactivate</a>
                                            <?php else: ?>
                                                <a href="doctors.php?toggle_status=<?php echo $doctor['doctor_id']; ?>" 
                                                   class="btn btn-success" style="padding: 5px 10px; font-size: 0.85rem;"
                                                   onclick="return confirm('Are you sure you want to activate this doctor?');">Activate</a>
                                            <?php endif; ?>
                                            <a href="doctors.php?delete=<?php echo $doctor['doctor_id']; ?>" 
                                               class="btn btn-danger" style="padding: 5px 10px; font-size: 0.85rem;"
                                               onclick="return confirmDelete('Are you sure you want to delete this doctor?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">No doctors found.</p>
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
        searchTable('searchInput', 'doctorsTable');
    </script>
</body>
</html>
