<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'admins') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate POST data
$booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$status     = $_POST['status'] ?? '';

if ($booking_id <= 0 || !in_array($status, ['accepted', 'rejected'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request',
        'debug' => $_POST // REMOVE after testing
    ]);
    exit;
}

// Check booking exists
$stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit;
}
$stmt->close();

// Update booking status
$stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
$stmt->bind_param("si", $status, $booking_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => "Booking {$status} successfully"
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}

$stmt->close();
$conn->close();
