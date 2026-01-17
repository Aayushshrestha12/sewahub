<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'vendors') {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$vendor_id   = $_SESSION['user_id'];
$category    = $_POST['category'] ?? null;
$description = $_POST['description'] ?? null;
$price       = $_POST['price'] ?? null;
$available_from = $_POST['available_from'] ?? null;
$available_to   = $_POST['available_to'] ?? null;

// validate
if (!$category || !$description || !$price) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// Insert service (if not exists)
$stmt = $conn->prepare("INSERT INTO services (category, description) VALUES (?, ?) 
    ON DUPLICATE KEY UPDATE description = VALUES(description)");
$stmt->bind_param("ss", $category, $description);
$stmt->execute();

// Get service_id
$service_id = $conn->insert_id ?: $conn->query("SELECT service_id FROM services WHERE category = '$category'")->fetch_assoc()['service_id'];

// Insert vendor_service
$stmt = $conn->prepare("INSERT INTO vendor_service (vendor_id, service_id, price, available_from, available_to) 
    VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iidss", $vendor_id, $service_id, $price, $available_from, $available_to);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Service added successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to add service"]);
}

$stmt->close();
$conn->close();