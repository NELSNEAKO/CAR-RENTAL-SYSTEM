<?php
session_start();
require_once 'php/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: log-in.php");
    exit;
}

// Check if vehicle ID is provided
if (!isset($_GET['id'])) {
    header("Location: home.php");
    exit;
}

$vehicle_id = (int)$_GET['id'];

// Fetch vehicle details
$query = "SELECT v.*, vc.name as category_name 
          FROM vehicles v 
          LEFT JOIN vehicle_categories vc ON v.category_id = vc.id 
          WHERE v.id = ? AND v.status = 'available'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
    header("Location: home.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        // Validate dates
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $today = new DateTime();
        
        if ($start < $today) {
            throw new Exception("Start date cannot be in the past.");
        }
        
        if ($end <= $start) {
            throw new Exception("End date must be after start date.");
        }
        
        // Calculate number of days
        $interval = $start->diff($end);
        $days = $interval->days;
        
        if ($days < 1) {
            throw new Exception("Minimum rental period is 1 day.");
        }
        
        // Calculate total amount
        $total_amount = $days * $vehicle['daily_rate'];
        
        // Insert booking
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, vehicle_id, start_date, end_date, total_amount) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissd", $_SESSION['user_id'], $vehicle_id, $start_date, $end_date, $total_amount);
        
        if ($stmt->execute()) {
            $booking_id = $conn->insert_id;
            header("Location: booking_confirmation.php?id=" . $booking_id);
            exit;
        } else {
            throw new Exception("Error creating booking.");
        }
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
    <title>Book Vehicle - QuadRide Rental</title>
    <link rel="stylesheet" href="home.css">
    <style>
        .booking-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .vehicle-details {
            display: flex;
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .vehicle-image {
            width: 300px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }

        .vehicle-info {
            flex: 1;
        }

        .booking-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .total-amount {
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
            font-size: 18px;
            font-weight: bold;
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

        .back-btn {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: #555;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            color: #000;
        }
    </style>
</head>
<body>
    <header class="navbar">
        

    <div class="booking-container">
        <a href="home.php" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/>
            </svg>
            Back to Home
        </a>

        <h2>Book Vehicle</h2>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="vehicle-details">
            <?php if (!empty($vehicle['image_path'])): ?>
                <img src="/rental/CAR-RENTAL-SYSTEM/car-rental-system/vehicles/<?php echo htmlspecialchars($vehicle['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>"
                     class="vehicle-image">
            <?php endif; ?>
            
            <div class="vehicle-info">
                <h3><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></h3>
                <p>Category: <?php echo htmlspecialchars($vehicle['category_name']); ?></p>
                <p>Year: <?php echo htmlspecialchars($vehicle['year']); ?></p>
                <p>Color: <?php echo htmlspecialchars($vehicle['color']); ?></p>
                <p>Daily Rate: ₱<?php echo number_format($vehicle['daily_rate'], 2); ?></p>
            </div>
        </div>

        <form method="POST" class="booking-form" id="bookingForm">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" required 
                       min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" required 
                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>

            <div class="total-amount" id="totalAmount">
                Total Amount: ₱0.00
            </div>

            <button type="submit" class="submit-btn">Proceed to Payment</button>
        </form>
    </div>

    <script>
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const totalAmount = document.getElementById('totalAmount');
        const dailyRate = <?php echo $vehicle['daily_rate']; ?>;

        function calculateTotal() {
            if (startDate.value && endDate.value) {
                const start = new Date(startDate.value);
                const end = new Date(endDate.value);
                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                
                if (days > 0) {
                    const total = days * dailyRate;
                    totalAmount.textContent = `Total Amount: ₱${total.toFixed(2)}`;
                } else {
                    totalAmount.textContent = 'Total Amount: ₱0.00';
                }
            }
        }

        startDate.addEventListener('change', () => {
            endDate.min = startDate.value;
            calculateTotal();
        });

        endDate.addEventListener('change', calculateTotal);
    </script>
</body>
</html> 