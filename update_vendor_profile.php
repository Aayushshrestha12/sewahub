<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

$vendor_id = $_SESSION['user_id'] ?? 0;
if(!$vendor_id){
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$skills = $_POST['skills'] ?? '';

$stmt = $conn->prepare("UPDATE vendors SET name=?, email=?, phone=?, skills=? WHERE vendor_id=?");
$stmt->bind_param("ssssi", $name, $email, $phone, $skills, $vendor_id);
$success = $stmt->execute();
$stmt->close();

if($success){
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
}
