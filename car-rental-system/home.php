<?php
require_once 'php/database.php';

// Fetch all available vehicles with their categories
$query = "
    SELECT v.*, vc.name as category_name 
    FROM vehicles v 
    LEFT JOIN vehicle_categories vc ON v.category_id = vc.id
    WHERE v.status = 'available'
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
            <a href="about.php">About Us</a>
            <a href="carlist.php">Car list</a>
            <a href="contact.php">Contact</a>
            <a href="gallery.php">Gallery</a>
            <a href="logout.php">Log Out</a>
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
                    <?php if (!empty($vehicle['image_path']) && file_exists($vehicle['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($vehicle['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>"
                             class="car-image">
                    <?php else: ?>
                        <div class="no-image">No Image Available</div>
                    <?php endif; ?>

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
                            <a href="details.php?id=<?php echo $vehicle['id']; ?>" class="details-btn">View Details</a>
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