<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])) exit('Not logged in');

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=? WHERE id=?");
$stmt->bind_param("sssssi", $data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['address'], $_SESSION['user_id']);
if($stmt->execute()){
    echo "Profile updated successfully!";
}else{
    echo "Error updating profile.";
}
?>
