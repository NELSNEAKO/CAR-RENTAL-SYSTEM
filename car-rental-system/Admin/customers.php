<?php
session_start();
require_once "../php/database.php";

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check database structure
$check_query = "DESCRIBE users";
$check_result = $conn->query($check_query);

if ($check_result) {
    echo "<!-- Database Structure Check: -->";
    echo "<!-- Users table columns: -->";
    while ($row = $check_result->fetch_assoc()) {
        echo "<!-- " . $row['Field'] . " - " . $row['Type'] . " -->";
    }
} else {
    echo "<!-- Error checking database structure: " . $conn->error . " -->";
}

// Fetch all customers with their booking history and vehicle details
$query = "SELECT u.*, 
          COUNT(DISTINCT b.id) as total_bookings,
          SUM(CASE WHEN b.status = 'confirmed' THEN b.total_amount ELSE 0 END) as total_spent,
          GROUP_CONCAT(
              CONCAT(
                  v.brand, ' ', v.model, ' (', 
                  DATE_FORMAT(b.start_date, '%M %d, %Y'), ' to ',
                  DATE_FORMAT(b.end_date, '%M %d, %Y'), 
                  ' - Status: ', b.status,
                  ' - Amount: ₱', FORMAT(b.total_amount, 2),
                  ')'
              ) SEPARATOR '||'
          ) as rental_history
          FROM users u
          LEFT JOIN bookings b ON u.id = b.user_id
          LEFT JOIN vehicles v ON b.vehicle_id = v.id
          WHERE u.role = 'customer'
          GROUP BY u.id
          ORDER BY u.first_name, u.last_name";

$result = $conn->query($query);

// Debug information
if (!$result) {
    echo "<!-- Query Error: " . $conn->error . " -->";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customers - Admin Panel</title>
    <link rel="stylesheet" href="admin_style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
            display: flex;
            min-height: 100vh;
            background: #f5f6fa;
        }

        .sidebar {
            width: 250px;
            background: #1a1d28;
            color: #ffffff;
            padding-top: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar-header h2 {
            margin: 0;
            color: #ffffff;
            font-size: 20px;
            font-weight: bold;
        }

        .sidebar-header p {
            margin: 5px 0 0;
            color: #cccccc;
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
            padding: 12px 20px;
            color: #ffffff;
            text-decoration: none;
            transition: background-color 0.3s ease;
            font-size: 15px;
        }

        .nav-menu a:hover {
            color: #ffffff;
        }

        .nav-menu a.active {
            background: #9b59b6;
            color: #ffffff;
        }

        /* Icons */
        .nav-menu a::before {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 15px;
            background-size: contain;
            background-repeat: no-repeat;
            filter: invert(100%);
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

        .logout-btn {
            display: flex;
            align-items: center;
            margin: 20px;
            padding: 12px 20px;
            background: #e74c3c;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s ease;
            font-weight: bold;
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
            background: #c0392b;
            color: white;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            background: #f5f6fa;
            min-height: 100vh;
        }

        .customers-container {
            padding: 20px;
        }

        .customers-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-box input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
        }

        .search-btn {
            padding: 8px 16px;
            background-color: #1abc9c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-btn:hover {
            background-color: #16a085;
        }

        .customers-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .customers-table th,
        .customers-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .customers-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .customers-table tr:hover {
            background-color: #f5f5f5;
        }

        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 5px;
        }

        .view-btn {
            background-color: #17a2b8;
            color: white;
        }

        .view-btn:hover {
            background-color: #138496;
        }

        .no-customers {
            text-align: center;
            padding: 40px;
            color: #666;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .customer-details {
            display: none;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-top: 10px;
        }

        .customer-details.active {
            display: block;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .detail-item {
            background: white;
            padding: 10px;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .detail-item h4 {
            margin: 0 0 5px 0;
            color: #666;
            font-size: 14px;
        }

        .detail-item p {
            margin: 0;
            color: #333;
            font-weight: bold;
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
            <li><a href="manage_vehicles.php">Manage Vehicles</a></li>
            <li><a href="customers.php" class="active">View Customers</a></li>
            <li><a href="view_reservations.php">Manage Reservations</a></li>
        </ul>

        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="main-content">
        <div class="customers-container">
            <div class="customers-header">
                <h2>Customer Management</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search customers...">
                    <button class="search-btn" onclick="searchCustomers()">Search</button>
                </div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <table class="customers-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Total Bookings</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($customer = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone_number']); ?></td>
                                <td><?php echo $customer['total_bookings']; ?></td>
                                <td>₱<?php echo number_format($customer['total_spent'], 2); ?></td>
                                <td>
                                    <span class="status-active">
                                        Active
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn view-btn" onclick="toggleCustomerDetails(<?php echo $customer['id']; ?>)">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="7">
                                    <div id="customer-details-<?php echo $customer['id']; ?>" class="customer-details">
                                        <div class="details-grid">
                                            <div class="detail-item">
                                                <h4>Address</h4>
                                                <p><?php echo htmlspecialchars($customer['address'] ?? 'Not provided'); ?></p>
                                            </div>
                                            <div class="detail-item">
                                                <h4>Account Information</h4>
                                                <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($customer['created_at'])); ?></p>
                                                <p><strong>Total Bookings:</strong> <?php echo $customer['total_bookings']; ?></p>
                                                <p><strong>Total Spent:</strong> ₱<?php echo number_format($customer['total_spent'], 2); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-customers">No customers found.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleCustomerDetails(customerId) {
            const detailsDiv = document.getElementById(`customer-details-${customerId}`);
            detailsDiv.classList.toggle('active');
        }

        function searchCustomers() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('.customers-table tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Add event listener for Enter key in search input
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCustomers();
            }
        });
    </script>
</body>
</html> 