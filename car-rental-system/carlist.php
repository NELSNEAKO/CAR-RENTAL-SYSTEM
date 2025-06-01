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
    </style>
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php">Log Out</a>
            <?php else: ?>
                <a href="log-in.php">Log In</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="car-list-container">
        <div class="search-section">
            <form class="search-form" method="GET">
                <select name="category" id="category">
                    <option value="">All Categories</option>
                    <?php
                    $cat_query = "SELECT * FROM vehicle_categories ORDER BY name";
                    $cat_result = $conn->query($cat_query);
                    while ($category = $cat_result->fetch_assoc()) {
                        $selected = (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : '';
                        echo "<option value='{$category['id']}' {$selected}>{$category['name']}</option>";
                    }
                    ?>
                </select>
                <select name="status" id="status">
                    <option value="">All Status</option>
                    <option value="available" <?php echo (isset($_GET['status']) && $_GET['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                    <option value="rented" <?php echo (isset($_GET['status']) && $_GET['status'] == 'rented') ? 'selected' : ''; ?>>Rented</option>
                </select>
                <input type="text" name="search" placeholder="Search by brand or model" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>

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