<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'admins') {
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit();
}

// Get POST JSON data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success'=>false, 'message'=>'Invalid request']);
    exit();
}

if (isset($data['action']) && $data['action'] === 'delete') {
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i", $data['user_id']);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>true, 'message'=>'User deleted']);
    exit();
}

// Update user
if (isset($data['user_id'])) {
    $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=? WHERE user_id=?");
    $stmt->bind_param(
        "sssssi",
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['phone'],
        $data['address'],
        $data['user_id']
    );
      if($stmt->execute()){
        echo json_encode(['success'=>true, 'message'=>'User updated successfully']);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Update failed']);
    }
    $stmt->close();
    exit();
}
    $stmt->close();
    echo json_encode(['success'=>true, 'message'=>'User updated successfully']);
    exit();


echo json_encode(['success'=>false, 'message'=>'Unknown request']);
