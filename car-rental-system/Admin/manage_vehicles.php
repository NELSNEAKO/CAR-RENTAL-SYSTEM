<?php
session_start();
require_once "../php/database.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle vehicle deletion
if (isset($_POST['delete_vehicle'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $vehicle_id);
    if ($stmt->execute()) {
        $message = "Vehicle deleted successfully.";
    } else {
        $error = "Error deleting vehicle.";
    }
}

// Fetch all vehicles with their categories
$query = "SELECT v.*, c.name as category_name 
          FROM vehicles v 
          LEFT JOIN vehicle_categories c ON v.category_id = c.id 
          ORDER BY v.id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles - QuadRide Rental</title>
    <link rel="stylesheet" href="admin_style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif; /* Simpler font */
            display: flex;
            min-height: 100vh;
            background: #f5f6fa; /* Match main content background */
        }

        .sidebar {
            width: 250px; /* Adjust width */
            background: #1a1d28; /* Dark background color from reference */
            color: #ffffff; /* White text color */
            padding-top: 20px; /* Add padding at the top */
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.5); /* Subtle shadow */
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 20px 20px; /* Adjust padding */
            border-bottom: 1px solid #333; /* Darker border */
            margin-bottom: 20px;
            text-align: center; /* Center header content */
        }

        .sidebar-header h2 {
            margin: 0;
            color: #ffffff; /* White color */
            font-size: 20px; /* Adjust font size */
            font-weight: bold;
        }

        .sidebar-header p {
            margin: 5px 0 0;
            color: #cccccc; /* Lighter gray for subtitle */
            font-size: 13px;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-menu li {
            margin: 0;
            padding: 0;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px; /* Adjust padding */
            color: #ffffff; /* White color for links */
            text-decoration: none;
            transition: background-color 0.3s ease; /* Transition for background */
            font-size: 15px;
        }

        .nav-menu a:hover {
            background: #2c3e50; /* Darker background on hover */
            color: #ffffff;
        }

        .nav-menu a.active {
            background: #9b59b6; /* Purple background from reference */
            color: #ffffff;
        }

        /* Icons */
        .nav-menu a::before {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 15px; /* Space between icon and text */
            background-size: contain;
            background-repeat: no-repeat;
            filter: invert(100%); /* Make SVG icons white */
        }

        .nav-menu a[href="dashboard.php"]::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>');
        }

        .nav-menu a[href="register.php"]::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>');
        }

        .nav-menu a[href="manage_vehicles.php"]::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>');
        }

        .nav-menu a[href="customers.php"]::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>');
        }

        .nav-menu a[href="view_reservations.php"]::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>');
        }

        .nav-menu a[href="reports.php"]::before {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>');
        }

        .logout-btn {
            display: flex;
            align-items: center;
            margin: 20px; /* Adjust margin */
            padding: 12px 20px; /* Adjust padding */
            background: #e74c3c; /* Red background */
            color: white; /* White text color */
            text-align: center;
            text-decoration: none;
            border-radius: 4px; /* Adjust border radius */
            transition: background 0.3s ease;
            font-weight: bold; /* Bold text */
        }

        .logout-btn::before {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 10px;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>');
            background-size: contain;
            background-repeat: no-repeat;
        }

        .logout-btn:hover {
            background: #c0392b; /* Darker red on hover */
            color: white;
        }

        .main-content {
            flex: 1;
            margin-left: 250px; /* Match sidebar width */
            padding: 20px; /* Adjust padding */
            background: #f5f6fa;
            min-height: 100vh;
        }

        .vehicles-container {
            max-width: 100%; /* Allow container to fill main-content */
            margin: 0; /* Remove auto margin */
            padding: 0; /* Remove padding */
        }

        .vehicles-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-vehicle-btn {
            background-color: #1abc9c;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .add-vehicle-btn:hover {
            background-color: #16a085;
        }

        .vehicles-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .vehicles-table th,
        .vehicles-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .vehicles-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .vehicles-table tr:hover {
            background-color: #f5f5f5;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            cursor: pointer;
        }

        .edit-btn {
            background-color: #3498db;
        }

        .delete-btn {
            background-color: #e74c3c;
        }

        .edit-btn:hover {
            background-color: #2980b9;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .status-available {
            color: #27ae60;
        }

        .status-rented {
            color: #e74c3c;
        }

        .status-maintenance {
            color: #f39c12;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>QuadRide Admin</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="register.php">Register New User</a></li>
            <li><a href="manage_vehicles.php" class="active">Manage Vehicles</a></li>
            <li><a href="customers.php">View Customers</a></li>
            <li><a href="view_reservations.php">Manage Reservations</a></li>
        </ul>

        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="main-content">
        <div class="vehicles-container">
            <div class="vehicles-header">
                <h2>Manage Vehicles</h2>
                <a href="add_vehicle.php" class="add-vehicle-btn">Add New Vehicle</a>
            </div>

            <?php if (isset($message)): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <table class="vehicles-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Category</th>
                        <th>Year</th>
                        <th>License Plate</th>
                        <th>Daily Rate</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($vehicle = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $vehicle['id']; ?></td>
                                <td>
                                    <?php if ($vehicle['image_path']): ?>
                                        <img src="<?php echo $vehicle['image_path']; ?>" alt="Vehicle" style="width: 100px; height: auto;">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($vehicle['brand']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['category_name'] ?? 'Uncategorized'); ?></td>
                                <td><?php echo $vehicle['year']; ?></td>
                                <td><?php echo htmlspecialchars($vehicle['license_plate']); ?></td>
                                <td>$<?php echo number_format($vehicle['daily_rate'], 2); ?></td>
                                <td class="status-<?php echo $vehicle['status']; ?>">
                                    <?php echo ucfirst($vehicle['status']); ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="edit_vehicle.php?id=<?php echo $vehicle['id']; ?>" class="edit-btn">Edit</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this vehicle?');">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                        <button type="submit" name="delete_vehicle" class="delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center;">No vehicles found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 