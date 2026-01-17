<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include("db.php");

$data = json_decode(file_get_contents("php://input"));

if(isset($data->id) && isset($data->status)){
    $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
    $stmt->bind_param("si", $data->status, $data->id);

    if($stmt->execute()){
        echo json_encode(["message" => "Booking updated"]);
    } else {
        echo json_encode(["error" => "Failed to update booking"]);
    }
} else {
    echo json_encode(["error" => "Invalid input"]);
}
?>
