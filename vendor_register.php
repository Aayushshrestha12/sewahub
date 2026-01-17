<?php
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$name       = $_POST['Name'] ?? '';
$skills     = $_POST['skills'] ?? '';
$location   = $_POST['location'] ?? '';
$experience = $_POST['experience'] ?? '';
$email      = $_POST['email'] ?? '';
$phone      = $_POST['phone'] ?? '';
$password   = $_POST['password'] ?? '';   // ✅ ADD
$status     = 'pending';

if (!$name || !$email || !$skills || !$password) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// ✅ HASH PASSWORD
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO vendor_applications 
    (name, skills, location, experience, email, phone, password, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssssss",
    $name,
    $skills,
    $location,
    $experience,
    $email,
    $phone,
    $hashedPassword,
    $status
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
