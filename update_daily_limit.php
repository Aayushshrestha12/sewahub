<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'vendors') {
    header("Location: login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];
$limit = (int)$_POST['daily_booking_limit'];

$stmt = $conn->prepare("
    UPDATE vendors 
    SET daily_limit = ?
    WHERE vendor_id = ?
");
$stmt->bind_param("ii", $limit, $vendor_id);
$stmt->execute();

$_SESSION['message'] = "Daily booking limit updated successfully.";
header("Location: vendor_dashboard.php");
exit;
