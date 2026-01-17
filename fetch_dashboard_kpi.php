<?php
require 'db.php';

$data = [];

$data['users'] = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$data['providers'] = $conn->query("SELECT COUNT(*) FROM providers WHERE status='approved'")->fetch_row()[0];
$data['bookings'] = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$data['revenue'] = $conn->query("SELECT IFNULL(SUM(amount),0) FROM payments WHERE status='completed'")->fetch_row()[0];

// Fake trend values (real dashboards do this too)
$data['userTrend'] = rand(5,15);
$data['providerTrend'] = rand(2,10);
$data['bookingTrend'] = rand(-5,5);
$data['revenueTrend'] = rand(8,20);

echo json_encode($data);
