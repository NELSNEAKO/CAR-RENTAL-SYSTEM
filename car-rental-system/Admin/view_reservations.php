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
    <link rel="stylesheet" href="../css/view_reservation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
</head>
<body>
    <!-- Add notification div -->
    <div id="notification" class="notification"></div>

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
            <li><a href="customers.php">View Customers</a></li>
            <li><a href="view_reservations.php" class="active">Manage Reservations</a></li>
        </ul>

        <a href="../php/logout.php" class="logout-btn">Logout</a>
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
                fetch('../php/update_booking_status.php', {
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
                fetch('../php/update_payment_status.php', {
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