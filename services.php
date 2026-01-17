<?php
header("Content-Type: application/json");
include("db.php");

$result = $conn->query("SELECT * FROM services");
$services = [];

while($row = $result->fetch_assoc()){
    $services[] = $row;
}

echo json_encode($services);
?>
