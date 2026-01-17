<?php
session_start();
include 'db.php';

$role = $_GET['role'] ?? '';
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if (!$role || !$email || !$token) {
    die('Invalid link');
}

// Determine table
$table = '';
if ($role === 'user') $table = 'users';
elseif ($role === 'vendor') $table = 'vendors';
elseif ($role === 'admin') $table = 'admins';
else die('Invalid role');

// Check token
$stmt = $conn->prepare("SELECT * FROM $table WHERE email = ? AND reset_token = ? AND reset_expiry > NOW()");
$stmt->bind_param("ss", $email, $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Invalid or expired token');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$password || $password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE $table SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE email = ?");
        $update->bind_param("ss", $hashed, $email);
        $update->execute();

        $success = 'Password reset successfully! You can now <a href="index.php">login</a>.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password - SewaHub</title>
<link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="reset-password-container">
    <h2>Reset Your Password</h2>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
    <?php if(isset($success)) { echo "<p class='success'>$success</p>"; } else { ?>
    <form method="POST">
        <input type="password" name="password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Reset Password</button>
    </form>
    <?php } ?>
</div>
</body>
</html>
