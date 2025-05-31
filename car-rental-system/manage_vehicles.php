<?php
require_once 'database.php';

// Delete vehicle if requested
if (isset($_GET['delete'])) {
    // Get the image path before deleting
    $stmt = $conn->prepare("SELECT image_path FROM rc_vehicles WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle = $result->fetch_assoc();
    
    // Delete the image file if it exists
    if ($vehicle && $vehicle['image_path'] && file_exists($vehicle['image_path'])) {
        unlink($vehicle['image_path']);
    }

    $stmt = $conn->prepare("DELETE FROM rc_vehicles WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    header("Location: manage_vehicles.php");
    exit();
}

// Fetch all vehicles with their categories
$query = "
    SELECT v.*, vc.name as category_name 
    FROM rc_vehicles v 
    LEFT JOIN rc_vehicle_categories vc ON v.category_id = vc.id
    ORDER BY v.created_at DESC
";
$result = $pdo->query($query);
$rc_vehicles = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Management - QuadRide Rental</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .vehicle-management {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .add-vehicle-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            display: inline-block;
        }
        .vehicle-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .vehicle-table th, .vehicle-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .vehicle-table th {
            background-color: #f5f5f5;
        }
        .action-btn {
            padding: 5px 10px;
            border-radius: 3px;
            text-decoration: none;
            margin-right: 5px;
        }
        .edit-btn {
            background-color: #2196F3;
            color: white;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
        }
        .status-available {
            color: green;
        }
        .status-rented {
            color: blue;
        }
        .status-maintenance {
            color: red;
        }
        .vehicle-image {
            width: 200px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }
        .no-image {
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="vehicle-management">
        <h1>Vehicle Management</h1>
        <a href="add_vehicle.php" class="add-vehicle-btn">Add New Vehicle</a>

        <table class="vehicle-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Category</th>
                    <th>Year</th>
                    <th>License Plate</th>
                    <th>Daily Rate</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rc_vehicles as $vehicle): ?>
                <tr>
                    <td>
                        <?php if ($vehicle['image_path'] && file_exists($vehicle['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($vehicle['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>"
                                 class="vehicle-image">
                        <?php else: ?>
                            <div class="vehicle-image no-image">No Image Available</div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($vehicle['brand']); ?></td>
                    <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                    <td><?php echo htmlspecialchars($vehicle['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($vehicle['year']); ?></td>
                    <td><?php echo htmlspecialchars($vehicle['license_plate']); ?></td>
                    <td>₱<?php echo number_format($vehicle['daily_rate'], 2); ?></td>
                    <td class="status-<?php echo strtolower($vehicle['status']); ?>">
                        <?php echo ucfirst($vehicle['status']); ?>
                    </td>
                    <td>
                        <a href="edit_vehicle.php?id=<?php echo $vehicle['id']; ?>" class="action-btn edit-btn">Edit</a>
                        <a href="manage_vehicles.php?delete=<?php echo $vehicle['id']; ?>" 
                           class="action-btn delete-btn" 
                           onclick="return confirm('Are you sure you want to delete this vehicle?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 