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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the booking ID and action
$booking_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($booking_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    if ($action === 'approve') {
        // Update payment status to completed
        $update_payment = "UPDATE payments SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE booking_id = ?";
        $stmt = $conn->prepare($update_payment);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        // If no payment record exists, create one
        if ($stmt->affected_rows === 0) {
            $create_payment = "INSERT INTO payments (booking_id, amount, payment_method, status, created_at, updated_at) 
                              SELECT id, total_amount, 'cash', 'completed', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP 
                              FROM bookings WHERE id = ?";
            $stmt = $conn->prepare($create_payment);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
        }

        // Update booking payment status to paid
        $update_booking = "UPDATE bookings SET payment_status = 'paid', status = 'confirmed', updated_at = CURRENT_TIMESTAMP 
                          WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($update_booking);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        // Update vehicle availability
        $update_vehicle = "UPDATE vehicles v 
                          JOIN bookings b ON v.id = b.vehicle_id 
                          SET v.status = 'rented', v.updated_at = CURRENT_TIMESTAMP 
                          WHERE b.id = ?";
        $stmt = $conn->prepare($update_vehicle);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        $message = 'Payment marked as completed';
    } else {
        // Update payment status to failed
        $update_payment = "UPDATE payments SET status = 'failed', updated_at = CURRENT_TIMESTAMP WHERE booking_id = ?";
        $stmt = $conn->prepare($update_payment);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        // If no payment record exists, create one
        if ($stmt->affected_rows === 0) {
            $create_payment = "INSERT INTO payments (booking_id, amount, payment_method, status, created_at, updated_at) 
                              SELECT id, total_amount, 'cash', 'failed', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP 
                              FROM bookings WHERE id = ?";
            $stmt = $conn->prepare($create_payment);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
        }

        // Update booking status to cancelled
        $update_booking = "UPDATE bookings SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP 
                          WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($update_booking);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        // Update vehicle availability back to available
        $update_vehicle = "UPDATE vehicles v 
                          JOIN bookings b ON v.id = b.vehicle_id 
                          SET v.status = 'available', v.updated_at = CURRENT_TIMESTAMP 
                          WHERE b.id = ?";
        $stmt = $conn->prepare($update_vehicle);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        $message = 'Payment marked as failed';
    }

    // Commit transaction
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error updating payment status: ' . $e->getMessage()]);
}
?> 