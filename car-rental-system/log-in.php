<?php
session_start();
require_once 'php/database.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if email and password are set
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Validate that email and password are not empty
        if (empty($email) || empty($password)) {
            $error = "Please fill in all fields.";
        } else {
            try {
                // Check if user exists
                $stmt = $conn->prepare("SELECT id, password, role, first_name, last_name FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($password, $user['password'])) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        
                        // Redirect based on role
                        if ($user['role'] === 'admin') {
                            header("Location: admin/dashboard.php");
                        } else {
                            header("Location: home.php");
                        }
                        exit();
                    } else {
                        $error = "Invalid password.";
                    }
                } else {
                    $error = "No account found with this email.";
                }
            } catch (Exception $e) {
                $error = "Login failed. Please try again later.";
                error_log("Login error: " . $e->getMessage());
            }
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - QuadRide Rental</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <header class="navbar">
        <div class="brand">
            <img src="carlogo2.png" alt="Car Logo" class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit;">
                <span class="brand-text">QuadRide<span class="highlight">Rental</span></span>
            </a>
        </div>

        <nav class="nav-links">
            <a href="about.php">About Us</a>
            <a href="carlist.php">Car list</a>
            <a href="contact.php">Contact</a>
            <a href="gallery.php">Gallery</a>
            <a href="staff.php">Staff</a>
            <a href="admin.php">Admin</a>
        </nav>
    </header>

    <div class="login-container">
        <div class="login-card">
            <h1 class="login-logo">QuadRide Rental</h1>
            <h2>Login your account</h2>

            <!-- <?php if ($error): ?>
                <div class="error-box">
                    <?php if (strpos($error, "email") !== false): ?>
                        <input class="input-error" type="email" placeholder="Email" disabled>
                    <?php else: ?>
                        <input class="input-error" type="password" placeholder="Password" disabled>
                    <?php endif; ?>
                    <p class="error-message">
                        <?= htmlspecialchars($error) ?>
                    </p>
                </div>
            <?php endif; ?> -->

            <form method="post">
                <input type="email" name="email" placeholder="Email Address" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="login-btn">Log In</button>
            </form>

            <a href="register.php" class="register-link">Don't have an account? Register here.</a>
        </div>
    </div>
</body>
</html>
