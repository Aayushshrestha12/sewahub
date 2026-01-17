<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'vendors') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$vendor_id = (int)$_SESSION['user_id'];
$id = (int)($_POST['vendor_service_id'] ?? 0);

if (!$id) {
    echo json_encode(['success'=>false,'message'=>'Invalid ID']);
    exit;
}

$stmt = $conn->prepare("
    DELETE FROM vendor_services
    WHERE id = ? AND vendor_id = ?
");
$stmt->bind_param("ii", $id, $vendor_id);
$stmt->execute();

echo json_encode(['success'=>true]);
exit;
