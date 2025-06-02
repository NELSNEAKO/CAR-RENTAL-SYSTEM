<?php
session_start();
include "../php/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - QuadRide Rental</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>QuadRide Admin</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="register.php">Register New User</a></li>
            <li><a href="manage_vehicles.php">Manage Vehicles</a></li>
            <li><a href="customers.php">View Customers</a></li>
            <li><a href="view_reservations.php">Manage Reservations</a></li>
        </ul>

        <a href="../php/logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="main-content">
        <div class="welcome-section">
            <h2>Welcome to the Admin Dashboard</h2>
            <p>Manage your car rental system efficiently</p>
        </div>

        <div class="stats-grid">
            <?php
            // Get total vehicles
            $vehicles_query = "SELECT COUNT(*) as total FROM vehicles";
            $vehicles_result = $conn->query($vehicles_query);
            $total_vehicles = $vehicles_result->fetch_assoc()['total'];

            // Get total customers
            $customers_query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
            $customers_result = $conn->query($customers_query);
            $total_customers = $customers_result->fetch_assoc()['total'];

            // Get total active reservations
            $reservations_query = "SELECT COUNT(*) as total FROM bookings WHERE status = 'confirmed'";
            $reservations_result = $conn->query($reservations_query);
            $total_reservations = $reservations_result->fetch_assoc()['total'];

            // Get available vehicles
            $available_query = "SELECT COUNT(*) as total FROM vehicles WHERE status = 'available'";
            $available_result = $conn->query($available_query);
            $total_available = $available_result->fetch_assoc()['total'];
            ?>

            <div class="stat-card">
                <h3>Total Vehicles</h3>
                <p><?php echo $total_vehicles; ?></p>
            </div>

            <div class="stat-card">
                <h3>Total Customers</h3>
                <p><?php echo $total_customers; ?></p>
            </div>

            <div class="stat-card">
                <h3>Active Reservations</h3>
                <p><?php echo $total_reservations; ?></p>
            </div>

            <div class="stat-card">
                <h3>Available Vehicles</h3>
                <p><?php echo $total_available; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
