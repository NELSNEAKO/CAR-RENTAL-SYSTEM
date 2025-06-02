<?php
// Get the current page name for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
    <header class="navbar">
        <div class="brand">
            <img src="carlogo2.png" alt="Car Logo" class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit;">
                <span class="brand-text">QuadRide<span class="highlight">Rental</span></span>
            </a>
        </div>

        <nav class="nav-links">
            <a href="index.php" <?php echo ($current_page == 'index.php') ? 'class="active"' : ''; ?>>Home</a>
            <a href="about.php" <?php echo ($current_page == 'about.php') ? 'class="active"' : ''; ?>>About Us</a>
            <a href="carlist.php" <?php echo ($current_page == 'carlist.php') ? 'class="active"' : ''; ?>>Car List</a>
            <a href="contact.php" <?php echo ($current_page == 'contact.php') ? 'class="active"' : ''; ?>>Contact</a>
            <a href="gallery.php" <?php echo ($current_page == 'gallery.php') ? 'class="active"' : ''; ?>>Gallery</a>
            <a href="staff.php" <?php echo ($current_page == 'staff.php') ? 'class="active"' : ''; ?>>Staff</a>
            <a href="Admin/login.php" class="admin-link">Admin</a>
        </nav>
    </header>

    <style>
        .navbar {
            background: #1a1d28;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo {
            height: 40px;
            width: auto;
        }

        .brand-text {
            font-size: 24px;
            font-weight: bold;
            color: #ffffff;
        }

        .highlight {
            color: #9b59b6;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            padding: 8px 15px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover, .nav-links a.active {
            background: #9b59b6;
            color: #ffffff;
        }

        .admin-link {
            background: #9b59b6;
            color: #ffffff;
        }

        .admin-link:hover {
            background: #8e44ad;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 15px;
            }

            .nav-links {
                margin-top: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-links a {
                font-size: 14px;
                padding: 6px 12px;
            }
        }
    </style>