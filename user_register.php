<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Invalid request']);
    exit;
}

// Get POST data
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$address    = trim($_POST['address'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$password   = $_POST['password'] ?? '';

// Basic validation
if (!$first_name || !$last_name || !$email || !$password) {
    echo json_encode(['success'=>false,'message'=>'All fields are required']);
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success'=>false,'message'=>'Email already registered']);
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into DB
$stmt = $conn->prepare("
    INSERT INTO users (first_name, last_name, email, password, phone, address) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("ssssss", $first_name, $last_name, $email, $hashedPassword, $phone, $address);

if ($stmt->execute()) {
    $_SESSION['user_id'] = $stmt->insert_id; // log in user after registration
    echo json_encode(['success'=>true,'message'=>'User registered successfully']);
} else {
    echo json_encode(['success'=>false,'message'=>'Registration failed: '.$stmt->error]);
}
