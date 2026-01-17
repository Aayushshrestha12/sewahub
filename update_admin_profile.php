<?php
session_start();
include 'db.php';

// Make sure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'admins') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // may be blank

    // Prepare update query
    if (!empty($password)) {
        // If password is provided, hash it
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET first_name=?, email=?, password=? WHERE id=?");
        $stmt->bind_param("ssssi", $first_name,$email, $hashedPassword, $_SESSION['user_id']);
    } else {
        // Password not changed
        $stmt = $conn->prepare("UPDATE admins SET first_name=?, email=? WHERE id=?");
        $stmt->bind_param("sssi", $first_name, $email, $_SESSION['user_id']);
    }

    if ($stmt->execute()) {
        $_SESSION['name'] = $first_name; // update session name
        $stmt->close();
        header("Location: admindashboard.php?success=Profile updated successfully");
        exit();
    } else {
        echo "Error updating profile: " . $stmt->error;
    }
} else {
    header("Location: admindashboard.php");
    exit();
}
