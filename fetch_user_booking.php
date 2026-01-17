<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

$bookings = $conn->query("SELECT * FROM bookings WHERE user_id=$user_id ORDER BY booking_date DESC")->fetch_all(MYSQLI_ASSOC);

echo json_encode($bookings);
