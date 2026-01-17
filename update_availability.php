<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'vendors') {
    header("Location: login.php");
    exit();
}

$vendor_service_id = intval($_POST['vendor_service_id']);
$available_from = $_POST['available_from'];
$available_to = $_POST['available_to'];

$stmt = $conn->prepare("
    UPDATE vendor_services
    SET available_from = ?, available_to = ?
    WHERE id = ?
");
$stmt->bind_param("ssi", $available_from, $available_to, $vendor_service_id);
$stmt->execute();
$stmt->close();

$_SESSION['message'] = "Availability updated successfully!";
header("Location: vendor_dashboard.php");
exit();
