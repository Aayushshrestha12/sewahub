<?php
include 'db.php';

if(isset($_GET['id']) && isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    // Fetch vendor application
    $vendor = $conn->query("SELECT * FROM vendor_applications WHERE id=$id")->fetch_assoc();

    if(!$vendor) {
        die("Vendor not found!");
    }

    if($action === 'approve') {
        // Insert into vendors table
        $stmt = $conn->prepare("INSERT INTO vendors (name,email,skills,location,phone,experience,is_approved) VALUES (?,?,?,?,?,?,1)");
        $stmt->bind_param("ssssss", $vendor['name'], $vendor['email'], $vendor['skills'], $vendor['location'], $vendor['phone'], $vendor['experience']);
        $stmt->execute();
        $stmt->close();

        // Update vendor application status
        $conn->query("UPDATE vendor_applications SET status='approved' WHERE id=$id");

        echo "Vendor approved successfully!";
    }
    elseif($action === 'reject') {
        // Update vendor application status
        $conn->query("UPDATE vendor_applications SET status='rejected' WHERE id=$id");

        echo "Vendor rejected!";
    }

    header("Location: admin_dashboard.php"); // redirect back to dashboard
}

