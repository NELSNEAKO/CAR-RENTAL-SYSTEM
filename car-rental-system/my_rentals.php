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
    <link rel="stylesheet" href="css/style.css">
    <style>
        .rentals-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .rentals-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .rentals-header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .rentals-header p {
            color: #666;
            font-size: 1.1em;
        }

        .rental-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .rental-card:hover {
            transform: translateY(-5px);
        }

        .rental-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .rental-id {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }

        .rental-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .status-pending {
            background: #ffeeba;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background: #cce5ff;
            color: #004085;
        }

        .rental-content {
            padding: 20px;
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
        }

        .vehicle-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        .rental-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .detail-group h3 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .detail-group p {
            color: #333;
            font-weight: bold;
            margin: 0;
        }

        .rental-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-amount {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }

        .payment-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .payment-pending {
            background: #ffeeba;
            color: #856404;
        }

        .payment-paid {
            background: #d4edda;
            color: #155724;
        }

        .no-rentals {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-rentals h2 {
            color: #666;
            margin-bottom: 20px;
        }

        .no-rentals a {
            display: inline-block;
            padding: 10px 20px;
            background: #1abc9c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .no-rentals a:hover {
            background: #16a085;
        }

        @media (max-width: 768px) {
            .rental-content {
                grid-template-columns: 1fr;
            }

            .vehicle-image {
                width: 100%;
                height: 200px;
            }

            .rental-details {
                grid-template-columns: 1fr;
            }
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background-color: #1abc9c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .back-button:hover {
            background-color: #16a085;
            transform: translateY(-2px);
        }

        .back-button:active {
            transform: translateY(0);
        }

        .back-button::before {
            content: '←';
            font-size: 20px;
        }
    </style>
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