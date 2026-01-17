<?php
session_start();
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$msg = "";

// Handle Approve/Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vendor_id'])) {
    $vendor_id = intval($_POST['vendor_id']);

    // APPROVE VENDOR
    if (isset($_POST['approve'])) {

        // 1️⃣ Fetch the vendor application
        $stmt = $conn->prepare("SELECT * FROM vendor_application WHERE id = ?");
        $stmt->bind_param("i", $vendor_id);
        $stmt->execute();
        $application = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($application) {
            // 2️⃣ Insert into vendors table
            $daily_limit = 5; // default daily limit
            $profile_photo = !empty($application['profile_photo']) ? $application['profile_photo'] : 'default.png';

            $stmt = $conn->prepare("
                INSERT INTO vendors 
                (name, email, password, skills, location, phone, experience, is_approved, daily_limit, profile_photo, created_at, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, NOW(), 'approved')
            ");
            $stmt->bind_param(
                "sssssssis",
                $application['name'],
                $application['email'],
                $application['password'], // already hashed
                $application['skills'],
                $application['location'],
                $application['phone'],
                $application['experience'],
                $daily_limit,
                $profile_photo
            );

            if ($stmt->execute()) {
                // 3️⃣ Update application status
                $stmt->close();
                $stmt = $conn->prepare("UPDATE vendor_application SET status = 'approved' WHERE id = ?");
                $stmt->bind_param("i", $vendor_id);
                $stmt->execute();
                $stmt->close();

                $msg = "✅ Vendor approved and added successfully!";
            } else {
                $msg = "❌ Error inserting vendor into vendors table.";
            }
        } else {
            $msg = "❌ Vendor application not found!";
        }
    }

    // REJECT VENDOR
    if (isset($_POST['reject'])) {
        $stmt = $conn->prepare("UPDATE vendor_application SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $vendor_id);
        $stmt->execute();
        $stmt->close();

        $msg = "❌ Vendor application rejected!";
    }

    header("Location: admin_vendor_approval.php?msg=" . urlencode($msg));
    exit();
}

// Fetch pending vendor applications
$pending_vendors = $conn->query("SELECT * FROM vendor_application WHERE status = 'pending'")->fetch_all(MYSQLI_ASSOC);

// Show any message passed in URL
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Approvals - Admin</title>
    <link rel="stylesheet" href="css/admin_dashboard.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f2f2f2; }
        button { margin-right: 5px; padding: 5px 10px; cursor: pointer; }
        .approve { background-color: #4CAF50; color: white; border: none; }
        .reject { background-color: #f44336; color: white; border: none; }
        .msg { margin: 10px 0; font-weight: bold; color: green; }
    </style>
</head>
<body>
    <h1>Pending Vendor Approvals</h1>
    <?php if($msg): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endif; ?>

    <?php if (!empty($pending_vendors)): ?>
    <table>
        <thead>
            <tr>
                <th>Vendor Name</th>
                <th>Email</th>
                <th>Skills</th>
                <th>Experience</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pending_vendors as $vendor): ?>
            <tr>
                <td><?= htmlspecialchars($vendor['name']) ?></td>
                <td><?= htmlspecialchars($vendor['email']) ?></td>
                <td><?= htmlspecialchars($vendor['skills']) ?></td>
                <td><?= htmlspecialchars($vendor['experience']) ?></td>
                <td>
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="vendor_id" value="<?= $vendor['id'] ?>">
                        <button class="approve" name="approve">Approve</button>
                        <button class="reject" name="reject">Reject</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No vendors pending approval.</p>
    <?php endif; ?>
</body>
</html>
