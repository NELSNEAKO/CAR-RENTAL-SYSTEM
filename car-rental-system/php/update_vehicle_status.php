<?php
require_once 'database.php';

function updateVehicleStatus() {
    global $conn;
    
    // Update vehicles that were rented but their rental period has ended
    $updateQuery = "
        UPDATE vehicles v
        SET v.status = 'available'
        WHERE v.status = 'rented'
        AND NOT EXISTS (
            SELECT 1 
            FROM bookings b 
            WHERE b.vehicle_id = v.id 
            AND b.status = 'confirmed'
            AND CURDATE() BETWEEN b.start_date AND b.end_date
        )
    ";
    
    if ($conn->query($updateQuery)) {
        return true;
    } else {
        return false;
    }
}

// Call the function when this file is included
updateVehicleStatus();
?> 