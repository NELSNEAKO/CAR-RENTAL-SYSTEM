<?php
session_start();
require_once 'php/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: log-in.php");
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    header("Location: home.php");
    exit;
}

$booking_id = (int)$_GET['id'];

// Fetch booking details
$query = "SELECT b.*, v.brand, v.model, v.image_path,
          u.first_name, u.last_name, u.email
          FROM bookings b
          JOIN vehicles v ON b.vehicle_id = v.id
          JOIN users u ON b.user_id = u.id
          WHERE b.id = ? AND b.user_id = ? AND b.status = 'confirmed'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header("Location: home.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Success - QuadRide Rental</title>
    <link rel="stylesheet" href="home.css">
    <style>
        .success-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #1abc9c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }

        .success-message {
            margin-bottom: 30px;
        }

        .success-message h2 {
            color: #1abc9c;
            margin-bottom: 10px;
        }

        .success-message p {
            color: #666;
            font-size: 18px;
        }

        .booking-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .detail-item {
            background: #fff;
            padding: 15px;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .detail-item h4 {
            margin: 0 0 10px 0;
            color: #666;
        }

        .detail-item p {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #1abc9c;
            color: white;
        }

        .btn-primary:hover {
            background-color: #16a085;
        }

        .btn-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    

    <div class="success-container">
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
            </svg>
        </div>

        <div class="success-message">
            <h2>Booking Confirmed!</h2>
            <p>Thank you for choosing QuadRide Rental. Your booking has been successfully processed.</p>
        </div>

        <div class="booking-details">
            <h3>Booking Details</h3>
            <div class="details-grid">
                <div class="detail-item">
                    <h4>Booking Reference</h4>
                    <p>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div class="detail-item">
                    <h4>Vehicle</h4>
                    <p><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></p>
                </div>
                <div class="detail-item">
                    <h4>Start Date</h4>
                    <p><?php echo date('F j, Y', strtotime($booking['start_date'])); ?></p>
                </div>
                <div class="detail-item">
                    <h4>End Date</h4>
                    <p><?php echo date('F j, Y', strtotime($booking['end_date'])); ?></p>
                </div>
                <div class="detail-item">
                    <h4>Total Amount</h4>
                    <p>₱<?php echo number_format($booking['total_amount'], 2); ?></p>
                </div>
                <div class="detail-item">
                    <h4>Status</h4>
                    <p>Confirmed</p>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="home.php" class="btn btn-primary">Return to Home</a>
            <a href="#" class="btn btn-secondary" onclick="window.print()">Print Receipt</a>
        </div>
    </div>
</body>
</html> 