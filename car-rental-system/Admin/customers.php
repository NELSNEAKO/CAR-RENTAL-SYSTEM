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
    <link rel="stylesheet" href="../css/customers.css">
    
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>QuadRide <?php echo $_SESSION['role'] === 'admin' ? 'Admin' : 'Staff'; ?></h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        
        <ul class="nav-menu">
        <li><a href="dashboard.php" >Dashboard</a></li>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="register.php">Register New User</a></li>
            <?php endif; ?>
            <li><a href="manage_vehicles.php">Manage Vehicles</a></li>
            <li><a href="customers.php" class="active">View Customers</a></li>
            <li><a href="view_reservations.php">Manage Reservations</a></li>
        </ul>

        <a href="../php/logout.php" class="logout-btn">Logout</a>
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