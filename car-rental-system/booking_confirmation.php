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

// Fetch booking details with vehicle and user information
$query = "SELECT b.*, v.brand, v.model, v.image_path, v.daily_rate,
          u.first_name, u.last_name, u.email, u.phone_number
          FROM bookings b
          JOIN vehicles v ON b.vehicle_id = v.id
          JOIN users u ON b.user_id = u.id
          WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header("Location: home.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $payment_method = $_POST['payment_method'];
        
        // Insert payment record
        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $booking_id, $booking['total_amount'], $payment_method);
        
        if ($stmt->execute()) {
            // Update booking status
            $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param("i", $booking_id);
            
            if ($stmt->execute()) {
                // Update vehicle status
                $stmt = $conn->prepare("UPDATE vehicles SET status = 'rented' WHERE id = ?");
                $stmt->bind_param("i", $booking['vehicle_id']);
                $stmt->execute();
                
                header("Location: booking_success.php?id=" . $booking_id);
                exit;
            }
        }
        throw new Exception("Error processing payment.");
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - QuadRide Rental</title>
    <link rel="stylesheet" href="home.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .booking-details {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .vehicle-info {
            display: flex;
            margin-bottom: 20px;
        }

        .vehicle-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
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

        .payment-section {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .payment-method {
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: #1abc9c;
        }

        .payment-method.selected {
            border-color: #1abc9c;
            background: #f0f9f6;
        }

        .submit-btn {
            background-color: #1abc9c;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background-color: #16a085;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

    <div class="confirmation-container">
        <h2>Booking Confirmation</h2>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="booking-details">
            <div class="vehicle-info">
                <?php if (!empty($booking['image_path'])): ?>
                        <img src="vehicles/<?php echo htmlspecialchars($booking['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>"
                         class="vehicle-image">
                <?php endif; ?>
                
                <div>
                    <h3><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></h3>
                    <p>Daily Rate: ₱<?php echo number_format($booking['daily_rate'], 2); ?></p>
                </div>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <h4>Booking Reference</h4>
                    <p>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div class="detail-item">
                    <h4>Total Amount</h4>
                    <p>₱<?php echo number_format($booking['total_amount'], 2); ?></p>
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
                    <h4>Customer Name</h4>
                    <p><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                </div>
                <div class="detail-item">
                    <h4>Contact Number</h4>
                    <p><?php echo htmlspecialchars($booking['phone_number']); ?></p>
                </div>
            </div>
        </div>

        <form method="POST" class="payment-section">
            <h3>Select Payment Method</h3>
            <div class="payment-methods">
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="credit_card" required>
                    <span>Credit Card</span>
                </label>
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="debit_card" required>
                    <span>Debit Card</span>
                </label>
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="cash" required>
                    <span>Cash</span>
                </label>
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="bank_transfer" required>
                    <span>Bank Transfer</span>
                </label>
            </div>

            <button type="submit" class="submit-btn">Confirm Payment</button>
        </form>
    </div>

    <script>
        // Add selected class to payment method when clicked
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', () => {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                method.classList.add('selected');
            });
        });
    </script>
</body>
</html> 