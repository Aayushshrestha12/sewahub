<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'admins') {
    echo json_encode(["success"=>false,"message"=>"Not authorized"]);
    exit();
}

$booking_id = intval($_POST['booking_id'] ?? 0);
$status     = $_POST['status'] ?? '';

if (!$booking_id || !in_array($status, ['accepted','rejected'])) {
    echo json_encode(["success"=>false,"message"=>"Invalid booking request"]);
    exit();
}

// Update booking
$stmt = $conn->prepare("UPDATE bookings SET status=? WHERE booking_id=?");
$stmt->bind_param("si", $status, $booking_id);

if ($stmt->execute()) {
    echo json_encode(["success"=>true,"message"=>"Booking $status successfully"]);
} else {
    echo json_encode(["success"=>false,"message"=>"Database error"]);
}

$stmt->close();
?>
