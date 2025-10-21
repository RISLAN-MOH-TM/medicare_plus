<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - MediCare Plus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/../includes/auth.php';
    requireRole('admin');
    
    $conn = getDBConnection();
    
    // Handle service operations
    $message = '';
    $error = '';
    
    // Delete service
    if (isset($_GET['delete'])) {
        $service_id = (int)$_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
        $stmt->bind_param("i", $service_id);
        if ($stmt->execute()) {
            $message = "Service deleted successfully!";
        } else {
            $error = "Error deleting service.";
        }
        $stmt->close();
    }
    
    // Toggle active status
    if (isset($_GET['toggle'])) {
        $service_id = (int)$_GET['toggle'];
        $stmt = $conn->prepare("UPDATE services SET is_active = NOT is_active WHERE service_id = ?");
        $stmt->bind_param("i", $service_id);
        if ($stmt->execute()) {
            $message = "Service status updated successfully!";
        } else {
            $error = "Error updating service status.";
        }
        $stmt->close();
    }
    
    // Add new service
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
        $service_name = trim($_POST['service_name']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        $base_price = !empty($_POST['base_price']) ? (float)$_POST['base_price'] : null;
        $duration_minutes = !empty($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($service_name)) {
            $error = "Service name is required!";
        } else {
            $stmt = $conn->prepare("INSERT INTO services (service_name, category, description, base_price, duration_minutes, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdii", $service_name, $category, $description, $base_price, $duration_minutes, $is_active);
            
            if ($stmt->execute()) {
                $message = "Service added successfully!";
            } else {
                $error = "Error adding service: " . $conn->error;
            }
            $stmt->close();
        }
    }
    
    // Update existing service
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
        $service_id = (int)$_POST['service_id'];
        $service_name = trim($_POST['service_name']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        $base_price = !empty($_POST['base_price']) ? (float)$_POST['base_price'] : null;
        $duration_minutes = !empty($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($service_name)) {
            $error = "Service name is required!";
        } else {
            $stmt = $conn->prepare("UPDATE services SET service_name = ?, category = ?, description = ?, base_price = ?, duration_minutes = ?, is_active = ? WHERE service_id = ?");
            $stmt->bind_param("sssdiii", $service_name, $category, $description, $base_price, $duration_minutes, $is_active, $service_id);
            
            if ($stmt->execute()) {
                $message = "Service updated successfully!";
            } else {
                $error = "Error updating service: " . $conn->error;
            }
            $stmt->close();
        }
    }
    
    // Get all services
    $services = $conn->query("
        SELECT * FROM services 
        ORDER BY category, service_name
    ");
    
    // Get unique categories for dropdown
    $categories_result = $conn->query("SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $existing_categories = [];
    while ($row = $categories_result->fetch_assoc()) {
        $existing_categories[] = $row['category'];
    }
    
    // Get service being edited
    $editing_service = null;
    if (isset($_GET['edit'])) {
        $edit_id = (int)$_GET['edit'];
        $stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $editing_service = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    
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
                        <li><a href="services.php">Services</a></li>
                        <li><a href="reset-password.php">Reset Password</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>Manage Services</h1>
                <p>Add, edit, or remove medical services</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Add/Edit Service Form -->
            <div class="card">
                <h2><?php echo $editing_service ? 'Edit Service' : 'Add New Service'; ?></h2>
                <form method="POST" class="form">
                    <?php if ($editing_service): ?>
                        <input type="hidden" name="service_id" value="<?php echo $editing_service['service_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="service_name">Service Name *</label>
                        <input type="text" id="service_name" name="service_name" required
                               value="<?php echo $editing_service ? htmlspecialchars($editing_service['service_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" list="categories"
                               value="<?php echo $editing_service ? htmlspecialchars($editing_service['category']) : ''; ?>"
                               placeholder="e.g., Cardiology, Laboratory, Radiology">
                        <datalist id="categories">
                            <?php foreach ($existing_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"><?php echo $editing_service ? htmlspecialchars($editing_service['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="base_price">Base Price (Rs.)</label>
                            <input type="number" id="base_price" name="base_price" step="0.01" min="0"
                                   value="<?php echo $editing_service && $editing_service['base_price'] ? $editing_service['base_price'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="duration_minutes">Duration (minutes)</label>
                            <input type="number" id="duration_minutes" name="duration_minutes" min="0"
                                   value="<?php echo $editing_service && $editing_service['duration_minutes'] ? $editing_service['duration_minutes'] : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo (!$editing_service || $editing_service['is_active']) ? 'checked' : ''; ?>
                                   style="margin-right: 10px;">
                            Active (visible to patients)
                        </label>
                    </div>

                    <div class="d-flex gap-10">
                        <?php if ($editing_service): ?>
                            <button type="submit" name="update_service" class="btn btn-success">Update Service</button>
                            <a href="services.php" class="btn btn-secondary">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Services List -->
            <div class="card">
                <h2>All Services</h2>
                <?php if ($services->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service Name</th>
                                    <th>Category</th>
                                    <th>Base Price</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($service = $services->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $service['service_id']; ?></td>
                                        <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                        <td><?php echo htmlspecialchars($service['category'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php echo $service['base_price'] ? 'Rs. ' . number_format($service['base_price'], 2) : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <?php echo $service['duration_minutes'] ? $service['duration_minutes'] . ' min' : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <span style="padding: 5px 10px; border-radius: 3px; font-size: 0.85rem; font-weight: 600;
                                                background-color: <?php echo $service['is_active'] ? '#27ae60' : '#e74c3c'; ?>; 
                                                color: white;">
                                                <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-5">
                                                <a href="?edit=<?php echo $service['service_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                                <a href="?toggle=<?php echo $service['service_id']; ?>" 
                                                   class="btn btn-warning btn-sm"
                                                   onclick="return confirm('Toggle service status?')">
                                                    <?php echo $service['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </a>
                                                <a href="?delete=<?php echo $service['service_id']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Are you sure you want to delete this service?')">
                                                    Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--dark-gray); padding: 20px;">No services found. Add your first service above!</p>
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
</body>
</html>
