<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

$vendor_id = intval($_POST['vendor_id'] ?? 0);
$service_id = intval($_POST['service_id'] ?? 0);
$booking_date = $_POST['date'] ?? '';
$booking_time = $_POST['time'] ?? '';

/* ✅ FIXED VALIDATION */
if (!$vendor_id || !$service_id || !$booking_date || !$booking_time) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

/* ✅ Prevent past booking */
if (strtotime("$booking_date $booking_time") < time()) {
    echo json_encode(['success' => false, 'message' => 'Cannot book past date/time']);
    exit();
}

/* 1️⃣ Get vendor daily limit */
$stmt = $conn->prepare("SELECT daily_limit FROM vendors WHERE vendor_id=?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$dailyLimit = (int)($stmt->get_result()->fetch_assoc()['daily_limit'] ?? 0);
$stmt->close();

/* 2️⃣ Count bookings for that date */
if ($dailyLimit > 0) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total 
        FROM bookings 
        WHERE vendor_id=? AND booking_date=? 
          AND status IN ('pending','accepted')
    ");
    $stmt->bind_param("is", $vendor_id, $booking_date);
    $stmt->execute();
    $current = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    if ($current >= $dailyLimit) {
        echo json_encode([
            'success' => false,
            'message' => 'Vendor booking limit reached'
        ]);
        exit();
    }
}

/* 3️⃣ Time slot conflict check */
$stmt = $conn->prepare("
    SELECT 1 FROM bookings 
    WHERE vendor_id=? 
      AND booking_date=? 
      AND booking_time=? 
      AND status IN ('pending','accepted')
");
$stmt->bind_param("iss", $vendor_id, $booking_date, $booking_time);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Vendor not available at this time'
    ]);
    exit();
}
$stmt->close();

/* 4️⃣ Insert booking */
$insert = $conn->prepare("
    INSERT INTO bookings 
    (user_id, vendor_id, service_id, booking_date, booking_time, status, payment_status)
    VALUES (?, ?, ?, ?, ?, 'pending', 'unpaid')
");

$insert->bind_param(
    "iiiss",
    $user_id,
    $vendor_id,
    $service_id,
    $booking_date,
    $booking_time
);

if ($insert->execute()) {
    echo json_encode([
        'success' => true,
        'booking_id' => $insert->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $insert->error
    ]);
}

$insert->close();
