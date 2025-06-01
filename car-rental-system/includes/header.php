                    <li><a href="carlist.php">Cars</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="my_rentals.php">My Rentals</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?> 