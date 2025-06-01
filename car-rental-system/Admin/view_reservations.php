<?php
session_start();
require_once "../php/database.php";

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch all reservations with customer and vehicle details
$query = "SELECT b.*, 
          u.first_name, u.last_name, u.email, u.phone_number,
          v.brand, v.model, v.license_plate, v.daily_rate,
          p.payment_method, p.status as payment_status
          FROM bookings b
          JOIN users u ON b.user_id = u.id
          JOIN vehicles v ON b.vehicle_id = v.id
          LEFT JOIN payments p ON b.id = p.booking_id
          ORDER BY b.created_at DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - Admin Panel</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

        .reservations-container {
            padding: 20px;
        }

        .reservations-header {
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

        .reservations-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .reservations-table th,
        .reservations-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .reservations-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .reservations-table tr:hover {
            background-color: #f5f5f5;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge i {
            font-size: 14px;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

        .status-refunded {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
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

        .confirm-btn {
            background-color: #28a745;
            color: white;
        }

        .confirm-btn:hover {
            background-color: #218838;
        }

        .cancel-btn {
            background-color: #dc3545;
            color: white;
        }

        .cancel-btn:hover {
            background-color: #c82333;
        }

        .reservation-details {
            display: none;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-top: 10px;
        }

        .reservation-details.active {
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

        .no-reservations {
            text-align: center;
            padding: 40px;
            color: #666;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Add notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            display: none;
            animation: slideIn 0.5s ease-out;
        }

        .notification.success {
            background-color: #28a745;
        }

        .notification.error {
            background-color: #dc3545;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .payment-approved {
            background-color: #28a745;
            color: #ffffff;
        }

        .payment-pending {
            background-color: #ffc107;
            color: #000000;
        }

        .payment-status-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-status-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .payment-status-date {
            font-size: 11px;
            color: #666;
        }

        .payment-actions {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .approve-payment-btn {
            background-color: #28a745;
            color: white;
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            transition: all 0.3s ease;
        }

        .approve-payment-btn:hover {
            background-color: #218838;
            transform: scale(1.1);
        }

        .approve-payment-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .approve-payment-btn i {
            font-size: 16px;
        }
    </style>
</head>
<body>
    <!-- Add notification div -->
    <div id="notification" class="notification"></div>

    <div class="sidebar">
        <div class="sidebar-header">
            <h2>QuadRide Admin</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="register.php">Register New User</a></li>
            <li><a href="manage_vehicles.php">Manage Vehicles</a></li>
            <li><a href="customers.php">View Customers</a></li>
            <li><a href="view_reservations.php" class="active">Manage Reservations</a></li>
        </ul>

        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="main-content">
        <div class="reservations-container">
            <div class="reservations-header">
                <h2>Reservation Management</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search reservations...">
                    <button class="search-btn" onclick="searchReservations()">Search</button>
                </div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <table class="reservations-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Dates</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?><br>
                                    <small><?php echo htmlspecialchars($booking['email']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?><br>
                                    <small>Plate: <?php echo htmlspecialchars($booking['license_plate']); ?></small>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> to<br>
                                    <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                                </td>
                                <td>₱<?php echo number_format($booking['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="payment-status-container">
                                        <div class="payment-status-info">
                                            <span class="status-badge status-<?php echo strtolower($booking['payment_status'] ?? 'pending'); ?>">
                                                <?php if ($booking['payment_status'] == 'completed'): ?>
                                                    <i class="fas fa-check-circle"></i> Payment Completed
                                                <?php elseif ($booking['payment_status'] == 'refunded'): ?>
                                                    <i class="fas fa-times-circle"></i> Payment Refunded
                                                <?php else: ?>
                                                    No Payment Status
                                                <?php endif; ?>
                                            </span>
                                            <?php if ($booking['payment_status'] == 'completed'): ?>
                                                <span class="payment-status-date">
                                                    Paid on: <?php echo date('M d, Y', strtotime($booking['updated_at'] ?? $booking['created_at'])); ?>
                                                </span>
                                            <?php elseif ($booking['payment_status'] == 'refunded'): ?>
                                                <span class="payment-status-date">
                                                    Refunded on: <?php echo date('M d, Y', strtotime($booking['updated_at'] ?? $booking['created_at'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!in_array($booking['payment_status'], ['completed', 'refunded'])): ?>
                                            <div class="payment-actions">
                                                <button class="approve-payment-btn" onclick="approvePayment(<?php echo $booking['id']; ?>, 'approve')" title="Mark as Completed">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="approve-payment-btn" style="background-color: #dc3545;" onclick="approvePayment(<?php echo $booking['id']; ?>, 'reject')" title="Mark as Failed">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <button class="action-btn view-btn" onclick="toggleReservationDetails(<?php echo $booking['id']; ?>)">
                                        View Details
                                    </button>
                                    <?php if ($booking['status'] == 'pending'): ?>
                                        <button class="action-btn confirm-btn" onclick="updateStatus(<?php echo $booking['id']; ?>, 'confirmed')">
                                            Confirm
                                        </button>
                                        <button class="action-btn cancel-btn" onclick="updateStatus(<?php echo $booking['id']; ?>, 'cancelled')">
                                            Cancel
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="8">
                                    <div id="reservation-details-<?php echo $booking['id']; ?>" class="reservation-details">
                                        <div class="details-grid">
                                            <div class="detail-item">
                                                <h4>Customer Details</h4>
                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?></p>
                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone_number'] ?? 'Not provided'); ?></p>
                                            </div>
                                            <div class="detail-item">
                                                <h4>Vehicle Details</h4>
                                                <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></p>
                                                <p><strong>License Plate:</strong> <?php echo htmlspecialchars($booking['license_plate']); ?></p>
                                                <p><strong>Daily Rate:</strong> ₱<?php echo number_format($booking['daily_rate'], 2); ?></p>
                                            </div>
                                            <div class="detail-item">
                                                <h4>Booking Details</h4>
                                                <p><strong>Start Date:</strong> <?php echo date('F j, Y', strtotime($booking['start_date'])); ?></p>
                                                <p><strong>End Date:</strong> <?php echo date('F j, Y', strtotime($booking['end_date'])); ?></p>
                                                <p><strong>Total Amount:</strong> ₱<?php echo number_format($booking['total_amount'], 2); ?></p>
                                            </div>
                                            <div class="detail-item">
                                                <h4>Payment Information</h4>
                                                <p><strong>Payment Method:</strong> <?php echo ucfirst($booking['payment_method'] ?? 'Not specified'); ?></p>
                                                <p><strong>Payment Status:</strong> <?php echo ucfirst($booking['payment_status'] ?? 'Pending'); ?></p>
                                                <p><strong>Booking Status:</strong> <?php echo ucfirst($booking['status']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-reservations">No reservations found.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';

            // Hide notification after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.5s ease-out';
                setTimeout(() => {
                    notification.style.display = 'none';
                    notification.style.animation = 'slideIn 0.5s ease-out';
                }, 500);
            }, 3000);
        }

        function toggleReservationDetails(reservationId) {
            const detailsDiv = document.getElementById(`reservation-details-${reservationId}`);
            detailsDiv.classList.toggle('active');
        }

        function searchReservations() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('.reservations-table tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function updateStatus(bookingId, status) {
            if (confirm(`Are you sure you want to ${status} this booking?`)) {
                // Create form data
                const formData = new FormData();
                formData.append('id', bookingId);
                formData.append('status', status);

                // Send AJAX request
                fetch('update_booking_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        // Reload the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('An error occurred while updating the status', 'error');
                    console.error('Error:', error);
                });
            }
        }

        function approvePayment(bookingId, action) {
            const actionText = action === 'approve' ? 'approve' : 'reject';
            if (confirm(`Are you sure you want to ${actionText} this payment?`)) {
                // Create form data
                const formData = new FormData();
                formData.append('id', bookingId);
                formData.append('action', action);

                // Send AJAX request
                fetch('update_payment_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        
                        // Find the payment actions container and hide it
                        const paymentActions = document.querySelector(`button[onclick="approvePayment(${bookingId}, 'approve')"]`).closest('.payment-actions');
                        if (paymentActions) {
                            paymentActions.style.display = 'none';
                        }

                        // Update the payment status display
                        const statusContainer = paymentActions.closest('.payment-status-container');
                        const statusBadge = statusContainer.querySelector('.status-badge');
                        const statusInfo = statusContainer.querySelector('.payment-status-info');
                        
                        if (action === 'approve') {
                            statusBadge.className = 'status-badge status-paid';
                            statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Payment Completed';
                            
                            // Add payment date
                            const dateSpan = document.createElement('span');
                            dateSpan.className = 'payment-status-date';
                            dateSpan.textContent = `Paid on: ${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
                            statusInfo.appendChild(dateSpan);
                        } else {
                            statusBadge.className = 'status-badge status-refunded';
                            statusBadge.innerHTML = '<i class="fas fa-times-circle"></i> Payment Failed';
                            
                            // Add failed date
                            const dateSpan = document.createElement('span');
                            dateSpan.className = 'payment-status-date';
                            dateSpan.textContent = `Failed on: ${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
                            statusInfo.appendChild(dateSpan);
                        }
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {   
                    showNotification('An error occurred while processing the payment', 'error');
                    console.error('Error:', error);
                });
            }
        }

        // Add event listener for Enter key in search input
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchReservations();
            }
        });
    </script>
</body>
</html> 