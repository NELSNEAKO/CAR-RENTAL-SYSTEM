<?php
session_start();
require_once '../php/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['is_admin'] = true;
                    
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "Invalid username or not an admin account.";
            }
        } catch (Exception $e) {
            $error = "Login failed. Please try again later.";
            error_log("Admin login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - QuadRide Rental</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('../car4.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            margin: auto;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            text-align: center;
        }

        .login-logo {
            color: #1abc9c;
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 20px;
            margin-bottom: 30px;
            color: #1c1e21;
        }

        .error-message {
            color: #ff4444;
            background-color: #fff8f8;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px;
            margin-bottom: 16px;
            border: 1px solid #dddfe2;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #1abc9c;
            box-shadow: 0 0 0 2px rgba(26, 188, 156, 0.2);
        }

        button {
            width: 100%;
            padding: 14px;
            background: #1abc9c;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background: #16a085;
        }

        button:active {
            transform: translateY(1px);
        }

        .back-link {
            display: block;
            margin-top: 20px;
            color: #1abc9c;
            text-decoration: none;
            font-size: 15px;
        }

        .back-link:hover {
            color: #16a085;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 10px;
            }
            
            .login-card {
                padding: 30px 20px;
            }
            
            .login-logo {
                font-size: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h1 class="login-logo">QuadRide Rental</h1>
            <h2>Admin Login</h2>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="text" name="username" placeholder="Username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>

            <a href="../index.php" class="back-link">Back to Main Site</a>
        </div>
    </div>
</body>
</html>