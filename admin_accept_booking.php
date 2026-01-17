<?php
include 'db.php';

if(!isset($_POST['booking_id'])){
    echo json_encode(['success'=>false, 'message'=>'Invalid booking request']);
    exit;
}

$booking_id = intval($_POST['booking_id']);

// Check if booking exists
$stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if(!$booking){
    echo json_encode(['success'=>false, 'message'=>'Booking not found']);
    exit;
}

// Update status
$stmt = $conn->prepare("UPDATE bookings SET status='accepted' WHERE booking_id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();

echo json_encode(['success'=>true]);
?>
