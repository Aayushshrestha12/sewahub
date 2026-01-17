<?php
header('Content-Type: application/json');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // include Composer autoloader
include 'db.php';

$email = $_POST['email'] ?? '';
$role = $_POST['role'] ?? 'user';

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

// Determine table
$table = '';
if ($role === 'user') $table = 'users';
elseif ($role === 'vendor') $table = 'vendors';
elseif ($role === 'admin') $table = 'admins';
else {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

// Check if email exists
$stmt = $conn->prepare("SELECT * FROM $table WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Email not found']);
    exit;
}

// Generate token
$token = bin2hex(random_bytes(16));
// Save token and expiry in DB if needed...

// Prepare reset link
$resetLink = "http://localhost/demo/reset_password.php?token=$token&role=$role";

// Send email using PHPMailer
$mail = new PHPMailer(true);
try {
    //Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // your SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your-email@gmail.com'; // your email
    $mail->Password   = 'your-email-app-password'; // Gmail app password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    //Recipients
    $mail->setFrom('your-email@gmail.com', 'SewaHub');
    $mail->addAddress($email);

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request';
    $mail->Body    = "Click the link to reset your password: <a href='$resetLink'>$resetLink</a>";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Reset link sent to your email!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Mailer Error: {$mail->ErrorInfo}"]);
}
