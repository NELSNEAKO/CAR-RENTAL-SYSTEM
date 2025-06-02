<?php
session_start();
require_once "../php/database.php";

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if it's an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate input
    $booking_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate status
    $allowed_statuses = ['confirmed', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Update booking status
    $query = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $booking_id);
    
    if ($stmt->execute()) {
        // If status is cancelled, update vehicle availability
        if ($status === 'cancelled') {
            $update_vehicle = "UPDATE vehicles v 
                             JOIN bookings b ON v.id = b.vehicle_id 
                             SET v.status = 'available' 
                             WHERE b.id = ?";
            $stmt = $conn->prepare($update_vehicle);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Booking status updated successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update booking status']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 