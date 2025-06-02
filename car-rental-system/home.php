<?php
session_start();
require_once 'php/database.php';
require_once 'php/update_vehicle_status.php';

// Fetch all available vehicles with their categories
$query = "
    SELECT v.*, vc.name as category_name 
    FROM vehicles v 
    LEFT JOIN vehicle_categories vc ON v.category_id = vc.id
    LEFT JOIN bookings b ON v.id = b.vehicle_id 
    AND b.status = 'confirmed'
    AND CURDATE() BETWEEN b.start_date AND b.end_date
    WHERE (v.status = 'available' OR 
           (v.status = 'rented' AND NOT EXISTS (
               SELECT 1 FROM bookings b2 
               WHERE b2.vehicle_id = v.id 
               AND b2.status = 'confirmed'
               AND CURDATE() BETWEEN b2.start_date AND b2.end_date
           )))
    GROUP BY v.id
    ORDER BY v.created_at DESC
";

$result = $conn->query($query);
$vehicles = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - QuadRide Rental</title>
    <link rel="stylesheet" href="home.css">

</head>
<body>
    <header class="navbar">
        <div class="brand">
            <img src="carlogo2.png" alt="Car Logo" class="logo">
            <span class="brand-text">QuadRide<span class="highlight">Rental</span></span>
        </div>

        <nav class="nav-links">
            <a href="home.php">Home</a>
            <a href="carlist.php">Car list</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="my_rentals.php">My Rentals</a>
                <a href="php/logout.php">Log Out</a>
            <?php else: ?>
                <a href="log-in.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="hero-home">
        <div class="hero-content">
            <h1>Welcome to QuadRide Rental</h1>
            <p>Find your perfect ride for any occasion</p>
        </div>
    </div>

    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search cars by brand, model, or category..." onkeyup="searchCars()">
    </div>

    <div class="section-title">
        <h1>AVAILABLE CARS FOR RENT</h1>
    </div>

    <div class="car-listing">
        <?php if (empty($vehicles)): ?>
            <div class="no-cars">
                <p>No vehicles available at the moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($vehicles as $vehicle): ?>
                <div class="flex-box" data-search="<?php echo strtolower($vehicle['brand'] . ' ' . $vehicle['model'] . ' ' . $vehicle['category_name']); ?>">
                    <?php 
                    $imagePath = !empty($vehicle['image_path']) ? 'Admin/' . $vehicle['image_path'] : 'Admin/uploads/vehicles/default-car.jpg';
                    $imagePath = str_replace('\\', '/', $imagePath); // Normalize path separators
                    ?>
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                         alt="<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>"
                         class="car-image"
                         onerror="this.src='Admin/uploads/vehicles/default-car.jpg'">

                    <div class="car-info">
                        <h3><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></h3>
                        <div class="car-category">
                            <?php echo htmlspecialchars($vehicle['category_name']); ?>
                        </div>
                        <div class="car-details">
                            <span>Year: <?php echo htmlspecialchars($vehicle['year']); ?></span>
                            <span>Color: <?php echo htmlspecialchars($vehicle['color']); ?></span>
                            <span>Plate: <?php echo htmlspecialchars($vehicle['license_plate']); ?></span>
                        </div>
                        <div class="fare">
                            <p>
                                Daily Rate: ₱<?php echo number_format($vehicle['daily_rate'], 2); ?>
                                <span class="status-badge">Available</span>
                            </p>
                        </div>
                        <div class="car-actions">
                            <a href="book.php?id=<?php echo $vehicle['id']; ?>" class="book-btn">Book Now</a>
                            <!-- <a href="details.php?id=<?php echo $vehicle['id']; ?>" class="details-btn">View Details</a> -->
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    function searchCars() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const cars = document.querySelectorAll('.flex-box');

        cars.forEach(car => {
            const searchText = car.getAttribute('data-search');
            if (searchText.includes(filter)) {
                car.style.display = '';
            } else {
                car.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>