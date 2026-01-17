<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'vendors'){
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

$vendor_id = $_SESSION['user_id'];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if($action === 'save'){
    $service_id = $_POST['service_id'] ?? null;
    $category = $_POST['category'];
    $description = $_POST['description'];
    $price = $_POST['price'] ?? null;

    if($service_id){ // Update
        $stmt = $conn->prepare("UPDATE services s JOIN vendor_service vs ON s.service_id = vs.service_id SET s.category=?, s.description=?, s.price=? WHERE vs.vendor_id=? AND s.service_id=?");
        $stmt->bind_param("ssdii", $category, $description, $price, $vendor_id, $service_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status'=>'success','message'=>'Service updated','service_id'=>$service_id,'category'=>$category,'description'=>$description,'price'=>$price]);
        exit;
    } else { // Insert
        $stmt = $conn->prepare("INSERT INTO services (category, description, price) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $category, $description, $price);
        $stmt->execute();
        $new_service_id = $conn->insert_id;

        $linkStmt = $conn->prepare("INSERT INTO vendor_services (vendor_id, service_id) VALUES (?, ?)");
        $linkStmt->bind_param("ii", $vendor_id, $new_service_id);
        $linkStmt->execute();
        $linkStmt->close();
        $stmt->close();

        echo json_encode(['status'=>'success','message'=>'Service added','service_id'=>$new_service_id,'category'=>$category,'description'=>$description,'price'=>$price]);
        exit;
    }
}

if($action === 'delete' && isset($_POST['service_id'])){
    $service_id = $_POST['service_id'];

    $stmt = $conn->prepare("DELETE FROM vendor_services WHERE vendor_id=? AND service_id=?");
    $stmt->bind_param("ii", $vendor_id, $service_id);
    $stmt->execute();
    $stmt->close();

    // Optionally delete service if no vendor links exist
    $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM vendor_services WHERE service_id=?");
    $checkStmt->bind_param("i", $service_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result()->fetch_assoc();
    if($result['cnt'] == 0){
        $delStmt = $conn->prepare("DELETE FROM services WHERE service_id=?");
        $delStmt->bind_param("i", $service_id);
        $delStmt->execute();
        $delStmt->close();
    }

    echo json_encode(['status'=>'success','message'=>'Service deleted','service_id'=>$service_id]);
    exit;
}
