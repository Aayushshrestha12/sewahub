<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'admins') {
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit();
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['vendor_id'], $input['action'])) {
    echo json_encode(['success'=>false, 'message'=>'Invalid request']);
    exit();
}

$vendor_id = intval($input['vendor_id']);
$action = $input['action'];

// Fetch vendor application
$stmt = $conn->prepare("SELECT * FROM vendor_applications WHERE vendor_id=? AND status='pending'");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success'=>false, 'message'=>'Vendor not found or already processed']);
    exit();
}

$vendor = $result->fetch_assoc();
$stmt->close();

if ($action === 'approve') {
    $insert = $conn->prepare("
        INSERT INTO vendors (name, email, skills, location, phone, experience, password, is_approved, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $insert->bind_param(
        "sssssss",
        $vendor['name'],
        $vendor['email'],
        $vendor['skills'],
        $vendor['location'],
        $vendor['phone'],
        $vendor['experience'],
        $vendor['password'] // make sure password exists
    );
    $insert->execute();
    $insert->close();

    // Update application status
    $update = $conn->prepare("UPDATE vendor_applications SET status='approved' WHERE vendor_id=?");
    $update->bind_param("i", $vendor_id);
    $update->execute();
    $update->close();

    echo json_encode(['success'=>true, 'message'=>'Vendor approved']);
    exit();
}

if ($action === 'reject') {
    $stmt = $conn->prepare("UPDATE vendor_applications SET status='rejected' WHERE vendor_id=?");
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success'=>true, 'message'=>'Vendor rejected']);
    exit();
}

echo json_encode(['success'=>false, 'message'=>'Unknown action']);
exit();
