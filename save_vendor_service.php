<?php
session_start();
include 'db.php';

$response = ['success' => false, 'message' => 'Unknown error'];

if(!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'vendors'){
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$vendor_id = $_SESSION['user_id'];
$vendor_service_id = $_POST['vendor_service_id'] ?? '';
$service_id = $_POST['service_id'] ?? '';
$price = $_POST['price'] ?? '';
$available_from = $_POST['available_from'] ?? '';
$available_to = $_POST['available_to'] ?? '';

if(empty($service_id) || empty($price) || empty($available_from) || empty($available_to)){
    $response['message'] = 'All fields are required';
    echo json_encode($response);
    exit;
}

try {
    if($vendor_service_id){
        // Update existing
        $stmt = $conn->prepare("UPDATE vendor_services SET service_id=?, price=?, available_from=?, available_to=? WHERE id=? AND vendor_id=?");
        $stmt->bind_param("iddsii", $service_id, $price, $available_from, $available_to, $vendor_service_id, $vendor_id);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO vendor_services (vendor_id, service_id, price, available_from, available_to) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidd", $vendor_id, $service_id, $price, $available_from, $available_to);
    }

    if($stmt->execute()){
        $response['success'] = true;
        $response['message'] = 'Service saved successfully';
    } else {
        $response['message'] = $stmt->error;
    }

} catch(Exception $e){
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
