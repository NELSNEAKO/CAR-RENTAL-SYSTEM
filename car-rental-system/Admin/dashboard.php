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
            /* background: #2c3e50;  */
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

        .welcome-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .welcome-section h2 {
            color: #2c3e50;
            margin: 0 0 10px;
        }

        .welcome-section p {
            color: #7f8c8d;
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #2c3e50;
            margin: 0 0 10px;
            font-size: 18px;
        }

        .stat-card p {
            color: #1abc9c;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
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
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="register.php">Register New User</a></li>
            <li><a href="manage_vehicles.php">Manage Vehicles</a></li>
            <li><a href="customers.php">View Customers</a></li>
            <li><a href="view_reservations.php">Manage Reservations</a></li>
        </ul>

        <a href="../logout.php" class="logout-btn">Logout</a>
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
