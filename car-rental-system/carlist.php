<?php
session_start();
require_once 'php/database.php';

// Fetch all vehicles with their categories and rental status
$query = "SELECT v.*, vc.name as category_name,
          CASE 
              WHEN b.id IS NOT NULL AND b.status = 'confirmed' 
              AND CURDATE() BETWEEN b.start_date AND b.end_date 
              THEN 'Rented'
              ELSE 'Available'
          END as rental_status
          FROM vehicles v
          LEFT JOIN vehicle_categories vc ON v.category_id = vc.id
          LEFT JOIN bookings b ON v.id = b.vehicle_id 
          AND b.status = 'confirmed'
          AND CURDATE() BETWEEN b.start_date AND b.end_date
          GROUP BY v.id
          ORDER BY v.brand, v.model";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car List - QuadRide Rental</title>
    <link rel="stylesheet" href="home.css">
    <style>
        .car-list-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .car-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .car-card:hover {
            transform: translateY(-5px);
        }

        .car-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .car-details {
            padding: 15px;
        }

        .car-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .car-info {
            color: #666;
            margin-bottom: 5px;
        }

        .car-price {
            font-size: 1.1em;
            color: #1abc9c;
            font-weight: bold;
            margin: 10px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .status-available {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rented {
            background-color: #f8d7da;
            color: #721c24;
        }

        .book-btn {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #1abc9c;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }

        .book-btn:hover {
            background-color: #16a085;
        }

        .book-btn.disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .search-section {
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .search-form select,
        .search-form input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .search-btn {
            background-color: #1abc9c;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .search-btn:hover {
            background-color: #16a085;
        }

        .no-cars {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: #555;
            margin-bottom: 20px;
            padding: 8px 16px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: #000;
            background-color: #f5f5f5;
        }

        .back-btn svg {
            margin-right: 8px;
        }
    </style>
</head>
<body>


    <div class="car-list-container">
        <a href="home.php" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/>
            </svg>
            Back to Home
        </a>

        <div class="car-grid">
            <?php
            if ($result->num_rows > 0) {
                while ($vehicle = $result->fetch_assoc()) {
                    $is_available = $vehicle['rental_status'] == 'Available';
                    ?>
                    <div class="car-card">
                        <img src="/rental/CAR-RENTAL-SYSTEM/car-rental-system/vehicles/<?php echo htmlspecialchars($vehicle['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>"
                             class="car-image">
                        <div class="car-details">
                            <div class="car-title">
                                <?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>
                            </div>
                            <div class="car-info">
                                Category: <?php echo htmlspecialchars($vehicle['category_name']); ?>
                            </div>
                            <div class="car-info">
                                Year: <?php echo htmlspecialchars($vehicle['year']); ?>
                            </div>
                            <div class="car-info">
                                Color: <?php echo htmlspecialchars($vehicle['color']); ?>
                            </div>
                            <div class="car-price">
                                ₱<?php echo number_format($vehicle['daily_rate'], 2); ?> / day
                            </div>
                            <span class="status-badge <?php echo $is_available ? 'status-available' : 'status-rented'; ?>">
                                <?php echo $vehicle['rental_status']; ?>
                            </span>
                            <?php if ($is_available): ?>
                                <a href="book.php?id=<?php echo $vehicle['id']; ?>" class="book-btn">Book Now</a>
                            <?php else: ?>
                                <span class="book-btn disabled">Currently Rented</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-cars">No vehicles found matching your criteria.</div>';
            }
            ?>
        </div>
    </div>
</body>
</html> 