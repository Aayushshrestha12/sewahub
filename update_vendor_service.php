<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'vendors') {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$vendor_id   = $_SESSION['user_id'];
$service_id  = $_POST['service_id'] ?? null;
$category    = $_POST['category'] ?? null;
$description = $_POST['description'] ?? null;
$price       = $_POST['price'] ?? null;
$available_from = $_POST['available_from'] ?? null;
$available_to   = $_POST['available_to'] ?? null;

if (!$service_id || !$category || !$description || !$price) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// Update services table
$stmt = $conn->prepare("UPDATE services SET category=?, description=? WHERE service_id=?");
$stmt->bind_param("ssi", $category, $description, $service_id);
$stmt->execute();

// Update vendor_service table
$stmt = $conn->prepare("UPDATE vendor_services SET price=?, available_from=?, available_to=? 
                        WHERE vendor_id=? AND service_id=?");
$stmt->bind_param("dssii", $price, $available_from, $available_to, $vendor_id, $service_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Service updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update service"]);
}

$stmt->close();
$conn->close();
