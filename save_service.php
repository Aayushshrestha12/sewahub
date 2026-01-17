<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'vendors') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$vendor_id = (int)$_SESSION['user_id'];

$vendor_service_id = $_POST['vendor_service_id'] ?? null;
$service_id        = $_POST['service_id'] ?? null;
$price             = $_POST['price'] ?? null;
$available_from    = $_POST['available_from'] ?? null;
$available_to      = $_POST['available_to'] ?? null;

// VALIDATION
if (!$service_id || !$price || !$available_from || !$available_to) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing fields',
        'debug'   => $_POST
    ]);
    exit;
}

// Convert price to float
$price = (float)$price;

// Convert datetime-local → MySQL DATETIME
$available_from = date('Y-m-d H:i:s', strtotime($available_from));
$available_to   = date('Y-m-d H:i:s', strtotime($available_to));

if ($vendor_service_id) {
    // UPDATE existing
    $stmt = $conn->prepare("
        UPDATE vendor_services
        SET service_id=?, price=?, available_from=?, available_to=?
        WHERE id=? AND vendor_id=?
    ");
    $stmt->bind_param("idssii",
        $service_id,
        $price,
        $available_from,
        $available_to,
        $vendor_service_id,
        $vendor_id
    );
} else {
    // INSERT new
    $stmt = $conn->prepare("
        INSERT INTO vendor_services
        (vendor_id, service_id, price, available_from, available_to)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iidss",
        $vendor_id,
        $service_id,
        $price,
        $available_from,
        $available_to
    );
}

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Service saved'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error'   => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
exit;
