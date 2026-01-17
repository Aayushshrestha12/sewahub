<?php
session_start();
include 'db.php';

// Ensure vendor is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'vendors') {
    header("Location: login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];

// Check if form is submitted
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $skills     = trim($_POST['skills'] ?? '');
    $location   = trim($_POST['location'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    
    if(empty($name) || empty($email)) {
        $_SESSION['error'] = "Name and Email are required.";
        header("Location: vendor_dashboard.php");
        exit();
    }

    // Check if vendor already has a pending application
    $checkQuery = $conn->prepare("SELECT * FROM vendor_applications WHERE vendor_id = ?");
    $checkQuery->bind_param("i", $vendor_id);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();
    
    if($checkResult->num_rows > 0) {
        $_SESSION['message'] = "You already have a pending application.";
        header("Location: vendor_dashboard.php");
        exit();
    }

    // Insert into vendor_applications
    $insertQuery = $conn->prepare("
        INSERT INTO vendor_applications
        (vendor_id, name, email, phone, skills, location, experience, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $insertQuery->bind_param("issssss", $vendor_id, $name, $email, $phone, $skills, $location, $experience);
    
    if($insertQuery->execute()) {
        $_SESSION['message'] = "Profile submitted for verification. Admin will review it soon.";
    } else {
        $_SESSION['error'] = "Something went wrong. Please try again.";
    }

    header("Location: vendor_dashboard.php");
    exit();
}
?>
