<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include("db.php");

$data = json_decode(file_get_contents("php://input"));

if(isset($data->id)){
    $stmt = $conn->prepare("UPDATE services SET name=?, description=?, price=? WHERE id=?");
    $stmt->bind_param("ssdi", $data->name, $data->description, $data->price, $data->id);

    if($stmt->execute()){
        echo json_encode(["message" => "Service updated successfully"]);
    } else {
        echo json_encode(["error" => "Failed to update service"]);
    }
} else {
    echo json_encode(["error" => "Invalid input"]);
}
?>
