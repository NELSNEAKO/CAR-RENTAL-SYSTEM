<?php
// Include database connection
require_once 'php/database.php';

$message = "";

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Registered successfully! You can now <a href='log-in.php'>log in</a>.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password match
    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Check if terms are accepted
        if (!isset($_POST['terms'])) {
            $message = "You must agree to the Terms and Conditions.";
        } else {
            try {
                // Check if email already exists
                $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $check_email->bind_param("s", $email);
                $check_email->execute();
                $result = $check_email->get_result();

                if ($result->num_rows > 0) {
                    $message = "An account with this email already exists.";
                } else {
                    // Hash the password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Split fullname into first and last name
                    $name_parts = explode(' ', $fullname);
                    $first_name = $name_parts[0];
                    $last_name = count($name_parts) > 1 ? implode(' ', array_slice($name_parts, 1)) : '';
                    
                    // Insert new user
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone_number, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
                    $stmt->bind_param("ssssss", $email, $email, $hashed_password, $first_name, $last_name, $mobile);
                    
                    if ($stmt->execute()) {
                        header("Location: register.php?success=1");
                        exit();
                    } else {
                        $message = "Registration failed. Please try again.";
                    }
                }
            } catch (Exception $e) {
                $message = "Registration failed. Please try again later.";
                // Log the error for debugging
                error_log("Registration error: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - QuadRide Rental</title>
    <link rel="stylesheet" href="register.css">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="register-container">
        <div class="register-card">
            <h1 class="register-logo">QuadRide Rental</h1>
            <h2>Create an account</h2>

            <?php if (!empty($message)): ?>
                <p class="<?= strpos($message, 'Registered successfully') !== false ? 'success-message' : 'error-message' ?>">
                    <?= $message ?>
                </p>
            <?php endif; ?>

            <form method="post">
                <input type="fullname" name="fullname" placeholder="Full Name" required>
                <input type="number" name="mobile" placeholder="Mobile Number" required>
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <div class="terms-checkbox">
                    <input type="checkbox" name="terms" id="terms" required>
                    <label for="terms">I Agree with <a href="terms.php" target="_blank" class="terms-link">Terms and Conditions</a></label>
                </div>
                <button type="submit" class="login-btn">Register</button>
            </form>

            <a href="log-in.php" class="register-link">Already have an account? Log in here.</a>
        </div>
    </div>
</body>
</html>