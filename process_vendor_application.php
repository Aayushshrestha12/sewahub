<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'admins'){
    header("Location: login.php");
    exit();
}

$application_id = $_POST['application_id'] ?? null;
$action = $_POST['action'] ?? null;

if(!$application_id || !in_array($action, ['accept','reject'])){
    die("Invalid request");
}

if($action === 'accept') {
    // Update application status
    $stmt = $conn->prepare("UPDATE vendor_applications SET status='accepted' WHERE application_id=?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $stmt->close();

    // Add vendor to vendors table as verified
    $appQuery = $conn->prepare("SELECT name, email, phone, skills, location, experience FROM vendor_applications WHERE application_id=?");
    $appQuery->bind_param("i", $application_id);
    $appQuery->execute();
    $result = $appQuery->get_result();
    $vendorData = $result->fetch_assoc();
    $appQuery->close();

    $insertVendor = $conn->prepare("
        INSERT INTO vendors (name, email, phone, skills, location, experience, is_approved)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    $insertVendor->bind_param(
        "ssssss",
        $vendorData['name'],
        $vendorData['email'],
        $vendorData['phone'],
        $vendorData['skills'],
        $vendorData['location'],
        $vendorData['experience']
    );
    $insertVendor->execute();
    $insertVendor->close();

} else {
    // Reject application
    $stmt = $conn->prepare("UPDATE vendor_applications SET status='rejected' WHERE application_id=?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: admin_vendor_applications.php");
exit();
