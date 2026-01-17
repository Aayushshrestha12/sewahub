<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'vendors') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$vendor_id = $_SESSION['user_id'];
$booking_id = intval($_POST['booking_id'] ?? 0);

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking']);
    exit();
}

/* ✅ Update ONLY accepted bookings */
$stmt = $conn->prepare("
    UPDATE bookings 
    SET status = 'completed'
    WHERE booking_id = ?
      AND vendor_id = ?
      AND status = 'accepted'
");

$stmt->bind_param("ii", $booking_id, $vendor_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No matching booking found or already completed'
    ]);
}

$stmt->close();
