<?php
session_start();
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'admins') {
    header("Location: login.php");
    exit();
}

// Add service
if (isset($_POST['add_service'])) {
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $stmt = $conn->prepare("INSERT INTO services (category, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $category, $description);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php?tab=manage-service");
    exit();
}

// Update service
if (isset($_POST['update_service']) && isset($_POST['service_id'])) {
    $id = intval($_POST['service_id']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $stmt = $conn->prepare("UPDATE services SET category=?, description=? WHERE service_id=?");
    $stmt->bind_param("ssi", $category, $description, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php?tab=manage-service");
    exit();
}

// Delete service
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php?tab=manage-service");
    exit();
}

header("Location: admin_dashboard.php");
exit();

