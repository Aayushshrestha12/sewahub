<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include("db.php");

if(isset($_GET['user_id'])){
    $stmt = $conn->prepare("
        SELECT b.id, b.booking_date, b.status, s.name AS service_name
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.user_id=?
    ");
    $stmt->bind_param("i", $_GET['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = [];
    while($row = $result->fetch_assoc()){
        $bookings[] = $row;
    }
    echo json_encode($bookings);
} else {
    $result = $conn->query("SELECT * FROM bookings");
    $bookings = [];
    while($row = $result->fetch_assoc()){
        $bookings[] = $row;
    }
    echo json_encode($bookings);
}
?>
