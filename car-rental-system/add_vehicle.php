<?php
require_once 'database.php';

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/vehicles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Fetch all vehicle categories
$stmt = $pdo->query("SELECT * FROM rc_vehicle_categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $image_path = null;
        
        // Handle image upload
        if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['vehicle_image']['name'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed)) {
                throw new Exception('Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.');
            }
            
            // Generate unique filename
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $upload_path)) {
                $image_path = $upload_path;
            } else {
                throw new Exception('Failed to upload image.');
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO rc_vehicles (
                category_id, brand, model, year, license_plate, 
                color, daily_rate, status, mileage, 
                last_maintenance_date, next_maintenance_date, image_path
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $_POST['category_id'],
            $_POST['brand'],
            $_POST['model'],
            $_POST['year'],
            $_POST['license_plate'],
            $_POST['color'],
            $_POST['daily_rate'],
            $_POST['status'],
            $_POST['mileage'],
            $_POST['last_maintenance_date'],
            $_POST['next_maintenance_date'],
            $image_path
        ]);

        header("Location: manage_vehicles.php");
        exit();
    } catch (Exception $e) {
        $error = "Error adding vehicle: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Vehicle - QuadRide Rental</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .add-vehicle-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .image-preview {
            max-width: 200px;
            margin-top: 10px;
        }
        #imagePreview {
            max-width: 100%;
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="add-vehicle-form">
        <h1>Add New Vehicle</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="vehicle_image">Vehicle Image</label>
                <input type="file" name="vehicle_image" id="vehicle_image" accept="image/*" required onchange="previewImage(this)">
                <div class="image-preview">
                    <img id="imagePreview" src="#" alt="Image Preview">
                </div>
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>
                <select name="category_id" id="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="brand">Brand</label>
                <input type="text" name="brand" id="brand" required>
            </div>

            <div class="form-group">
                <label for="model">Model</label>
                <input type="text" name="model" id="model" required>
            </div>

            <div class="form-group">
                <label for="year">Year</label>
                <input type="number" name="year" id="year" min="1900" max="<?php echo date('Y') + 1; ?>" required>
            </div>

            <div class="form-group">
                <label for="license_plate">License Plate</label>
                <input type="text" name="license_plate" id="license_plate" required>
            </div>

            <div class="form-group">
                <label for="color">Color</label>
                <input type="text" name="color" id="color" required>
            </div>

            <div class="form-group">
                <label for="daily_rate">Daily Rate (₱)</label>
                <input type="number" name="daily_rate" id="daily_rate" min="0" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" required>
                    <option value="available">Available</option>
                    <option value="rented">Rented</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <div class="form-group">
                <label for="mileage">Mileage (km)</label>
                <input type="number" name="mileage" id="mileage" min="0" required>
            </div>

            <div class="form-group">
                <label for="last_maintenance_date">Last Maintenance Date</label>
                <input type="date" name="last_maintenance_date" id="last_maintenance_date" required>
            </div>

            <div class="form-group">
                <label for="next_maintenance_date">Next Maintenance Date</label>
                <input type="date" name="next_maintenance_date" id="next_maintenance_date" required>
            </div>

            <button type="submit" class="submit-btn">Add Vehicle</button>
        </form>
    </div>

    <script>
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html> 