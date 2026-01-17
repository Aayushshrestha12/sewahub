<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'admins') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_SESSION['user_id'];

    $first_name = trim($_POST['first_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($first_name === '' || $email === '') {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }

    $photo = null; // Default: no new photo

    // Handle file upload if a new photo is selected
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $newFileName = "admin_".$id.".".$ext;
        $uploadDir = "uploads/admins/";

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $uploadPath = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadPath)) {
            $photo = $newFileName;
        }
    }

    // Build SQL dynamically depending on password/photo
    $params = [];
    $types = "";

    $sql = "UPDATE admins SET first_name=?, email=?";
    $params[] = $first_name;
    $params[] = $email;
    $types .= "ss";

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password=?";
        $params[] = $hashedPassword;
        $types .= "s";
    }

    if ($photo !== null) {
        $sql .= ", profile_photo=?";
        $params[] = $photo;
        $types .= "s";
    }

    $sql .= " WHERE admin_id=?";
    $params[] = $id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }

    $stmt->close();
    $conn->close();
}
?>
