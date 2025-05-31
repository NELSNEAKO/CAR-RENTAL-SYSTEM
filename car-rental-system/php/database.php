
<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$database = "car_rental_db";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed."]));
}

// Simple debug function
function debug($message) {
    echo "<div style='background-color: #e8f5e9; padding: 10px; margin: 10px; border: 1px solid #c8e6c9; border-radius: 4px;'>";
    echo $message;
    echo "</div>";
}

?>