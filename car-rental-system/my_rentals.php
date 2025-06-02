<?php
session_start();
require_once "php/database.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user's rentals with vehicle details
$query = "SELECT b.*, 
          v.brand, v.model, v.license_plate, v.image_path,
          p.payment_method, p.status as payment_status
          FROM bookings b
          JOIN vehicles v ON b.vehicle_id = v.id
          LEFT JOIN payments p ON b.id = p.booking_id
          WHERE b.user_id = ?
          ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rentals - QuadRide</title>
    <link rel="stylesheet" href="css/myrental.css">
    
</head>
<body>
    <button onclick="window.history.back()" class="back-button">Back</button>

    <div class="rentals-container">
        <div class="rentals-header">
            <h1>My Rentals</h1>
            <p>View your rental history and current bookings</p>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($rental = $result->fetch_assoc()): ?>
                <div class="rental-card">
                    <div class="rental-header">
                        <div class="rental-id">Booking #<?php echo $rental['id']; ?></div>
                        <span class="rental-status status-<?php echo strtolower($rental['status']); ?>">
                            <?php echo ucfirst($rental['status']); ?>
                        </span>
                    </div>
                    <div class="rental-content">
                        <img src="<?php echo !empty($rental['image_path']) ? 'Admin/' . $rental['image_path'] : 'Admin/uploads/vehicles/default-car.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($rental['brand'] . ' ' . $rental['model']); ?>" 
                             class="vehicle-image"
                             onerror="this.src='Admin/uploads/vehicles/default-car.jpg'">
                        <div class="rental-details">
                            <div class="detail-group">
                                <h3>Vehicle</h3>
                                <p><?php echo htmlspecialchars($rental['brand'] . ' ' . $rental['model']); ?></p>
                                <p>Plate: <?php echo htmlspecialchars($rental['license_plate']); ?></p>
                            </div>
                            <div class="detail-group">
                                <h3>Rental Period</h3>
                                <p>From: <?php echo date('F j, Y', strtotime($rental['start_date'])); ?></p>
                                <p>To: <?php echo date('F j, Y', strtotime($rental['end_date'])); ?></p>
                            </div>
                            <div class="detail-group">
                                <h3>Booking Date</h3>
                                <p><?php echo date('F j, Y', strtotime($rental['created_at'])); ?></p>
                            </div>
                            <div class="detail-group">
                                <h3>Payment Method</h3>
                                <p><?php echo ucfirst($rental['payment_method'] ?? 'Not specified'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="rental-footer">
                        <div class="total-amount">
                            Total: ₱<?php echo number_format($rental['total_amount'], 2); ?>
                        </div>
                        <span class="payment-status payment-<?php echo strtolower($rental['payment_status'] ?? 'pending'); ?>">
                            <?php echo ucfirst($rental['payment_status'] ?? 'Pending'); ?>
                        </span>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-rentals">
                <h2>You haven't made any rentals yet</h2>
                <a href="carlist.php">Browse Available Vehicles</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html> 