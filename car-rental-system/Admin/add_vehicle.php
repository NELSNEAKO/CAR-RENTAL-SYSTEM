<?php
session_start();
require_once "../php/database.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check if GD library is available
if (!extension_loaded('gd')) {
    function optimizeImage($source_path, $max_width = 1200, $max_height = 1200, $quality = 80) {
        // If GD is not available, just return the original file
        return $source_path;
    }
} else {
    // Add image optimization function
    function optimizeImage($source_path, $max_width = 1200, $max_height = 1200, $quality = 80) {
        list($width, $height, $type) = getimagesize($source_path);
        
        // Calculate new dimensions
        if ($width > $max_width || $height > $max_height) {
            $ratio = min($max_width / $width, $max_height / $height);
            $new_width = round($width * $ratio);
            $new_height = round($height * $ratio);
        } else {
            $new_width = $width;
            $new_height = $height;
        }

        // Create new image
        $new_image = imagecreatetruecolor($new_width, $new_height);

        // Handle different image types
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($source_path);
                // Preserve transparency for PNG
                imagealphablending($new_image, false);
                imagesavealpha($new_image, true);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($source_path);
                break;
            default:
                return false;
        }

        // Resize image
        imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Save optimized image
        $temp_path = tempnam(sys_get_temp_dir(), 'optimized_');
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($new_image, $temp_path, $quality);
                break;
            case IMAGETYPE_PNG:
                imagepng($new_image, $temp_path, round(9 * $quality / 100));
                break;
            case IMAGETYPE_GIF:
                imagegif($new_image, $temp_path);
                break;
        }

        // Clean up
        imagedestroy($source);
        imagedestroy($new_image);

        return $temp_path;
    }
}

$message = "";
$error = "";

// Fetch vehicle categories
$categories_query = "SELECT * FROM vehicle_categories ORDER BY name";
$categories_result = $conn->query($categories_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate and sanitize input
        $brand = trim($_POST['brand']);
        $model = trim($_POST['model']);
        $year = (int)$_POST['year'];
        $license_plate = trim($_POST['license_plate']);
        $color = trim($_POST['color']);
        $daily_rate = (float)$_POST['daily_rate'];
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $description = trim($_POST['description']);
        $features = trim($_POST['features']);
        $status = 'available';

        // Validate required fields
        if (empty($brand) || empty($model) || empty($license_plate) || empty($daily_rate)) {
            throw new Exception("Please fill in all required fields.");
        }

        // Handle image upload
        $image_path = null;
        if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['vehicle_image']['name'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed)) {
                throw new Exception('Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.');
            }
            
            $upload_dir = '../uploads/vehicles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            // Optimize image before saving
            $temp_path = $_FILES['vehicle_image']['tmp_name'];
            $optimized_path = optimizeImage($temp_path);
            
            if ($optimized_path && move_uploaded_file($optimized_path, $upload_path)) {
                $image_path = $upload_path;
                // Clean up temporary file
                @unlink($optimized_path);
            } else {
                throw new Exception('Failed to upload image.');
            }
        }

        // Insert vehicle into database
        $stmt = $conn->prepare("INSERT INTO vehicles (category_id, brand, model, year, license_plate, color, daily_rate, status, image_path, description, features) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississsssss", $category_id, $brand, $model, $year, $license_plate, $color, $daily_rate, $status, $image_path, $description, $features);
        
        if ($stmt->execute()) {
            $message = "Vehicle added successfully!";
            // Clear form data
            $_POST = array();
        } else {
            throw new Exception("Error adding vehicle to database.");
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
    <title>Add Vehicle - QuadRide Rental</title>
    <link rel="stylesheet" href="../css/add_vehicle.css">
    
</head>
<body>
    <div class="add-vehicle-container">
        <a href="manage_vehicles.php" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/>
            </svg>
            Back to Manage Vehicles
        </a>
        <h2>Add New Vehicle</h2>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="category_id">Category</label>
                <select name="category_id" id="category_id">
                    <option value="">Select Category</option>
                    <?php while($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="brand">Brand *</label>
                <input type="text" name="brand" id="brand" required value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="model">Model *</label>
                <input type="text" name="model" id="model" required value="<?php echo isset($_POST['model']) ? htmlspecialchars($_POST['model']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="year">Year *</label>
                <input type="number" name="year" id="year" required min="1900" max="<?php echo date('Y')+1; ?>" value="<?php echo isset($_POST['year']) ? htmlspecialchars($_POST['year']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="license_plate">License Plate *</label>
                <input type="text" name="license_plate" id="license_plate" required value="<?php echo isset($_POST['license_plate']) ? htmlspecialchars($_POST['license_plate']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="color">Color</label>
                <input type="text" name="color" id="color" value="<?php echo isset($_POST['color']) ? htmlspecialchars($_POST['color']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="daily_rate">Daily Rate ($) *</label>
                <input type="number" name="daily_rate" id="daily_rate" required min="0" step="0.01" value="<?php echo isset($_POST['daily_rate']) ? htmlspecialchars($_POST['daily_rate']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="vehicle_image">Vehicle Image</label>
                <input type="file" name="vehicle_image" id="vehicle_image" accept="image/*">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="features">Features</label>
                <textarea name="features" id="features"><?php echo isset($_POST['features']) ? htmlspecialchars($_POST['features']) : ''; ?></textarea>
            </div>

            <button type="submit" class="submit-btn">Add Vehicle</button>
        </form>
    </div>
</body>
</html> 