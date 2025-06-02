<?php
session_start();
require_once 'php/database.php';
require_once 'php/update_vehicle_status.php';

// Fetch all vehicles with their categories and rental status
$query = "SELECT v.*, vc.name as category_name,
          CASE 
              WHEN v.status = 'maintenance' THEN 'Maintenance'
              WHEN v.status = 'rented' AND EXISTS (
                  SELECT 1 FROM bookings b 
                  WHERE b.vehicle_id = v.id 
                  AND b.status = 'confirmed'
                  AND CURDATE() BETWEEN b.start_date AND b.end_date
              ) THEN 'Rented'
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
                            <span class="status-badge <?php 
                                echo $vehicle['rental_status'] == 'Available' ? 'status-available' : 
                                    ($vehicle['rental_status'] == 'Rented' ? 'status-rented' : 'status-maintenance'); 
                            ?>">
                                <?php echo $vehicle['rental_status']; ?>
                            </span>
                            <?php if ($vehicle['rental_status'] == 'Available'): ?>
                                <a href="book.php?id=<?php echo $vehicle['id']; ?>" class="book-btn">Book Now</a>
                            <?php else: ?>
                                <span class="book-btn disabled"><?php echo $vehicle['rental_status']; ?></span>
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