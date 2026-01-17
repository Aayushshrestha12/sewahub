<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'users') {
    echo json_encode([]);
    exit();
}

$category = $_POST['category'] ?? '';
$location = $_POST['location'] ?? '';
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';

$sql = "
SELECT s.service_id, s.category, s.description,
       v.vendor_id, v.name AS vendor_name, v.location,
       vs.price
FROM services s
JOIN vendor_services vs ON s.service_id = vs.service_id
JOIN vendors v ON vs.vendor_id = v.vendor_id
WHERE s.category LIKE ? AND v.location LIKE ? AND v.is_approved = 1
";

$stmt = $conn->prepare($sql);
$likeCat = "%$category%";
$likeLoc = "%$location%";
$stmt->bind_param("ss", $likeCat, $likeLoc);
$stmt->execute();
$res = $stmt->get_result();

$vendors = [];
while ($row = $res->fetch_assoc()) {
    // Check availability
    $check = $conn->prepare("SELECT * FROM bookings WHERE vendor_id=? AND booking_date=? AND booking_time=? AND status IN ('pending','confirmed')");
    $check->bind_param("iss", $row['vendor_id'], $date, $time);
    $check->execute();
    $avail = $check->get_result();
    $row['is_available'] = ($avail->num_rows === 0);
    $row['date'] = $date;
    $row['time'] = $time;
    $vendors[] = $row;
    $check->close();
}

echo json_encode($vendors);
