<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
if(!$user_id) {
    die("⚠ You must be logged in.");
}

// eSewa POST parameters
$booking_id = intval($_POST['pid'] ?? 0);
$refId      = $_POST['refId'] ?? '';

if(!$booking_id || !$refId){
    die("⚠ Invalid payment data.");
}

// Optional: Verify with eSewa server here using their verification API
// https://esewa.com.np/#developer-documentation

// 1️⃣ Update payments table
$stmt = $conn->prepare("
    UPDATE payments
    SET status='completed', pay_method='esewa', pay_date=NOW()
    WHERE booking_id=? AND user_id=? AND status='pending'
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$stmt->close();

// 2️⃣ Update bookings table
$stmt2 = $conn->prepare("
    UPDATE bookings
    SET payment_status='paid'
    WHERE booking_id=? AND user_id=?
");
$stmt2->bind_param("ii", $booking_id, $user_id);
$stmt2->execute();
$stmt2->close();

// 3️⃣ Show confirmation
echo "<h2>✅ Payment Successful!</h2>";
echo "<p>Your booking #$booking_id is now paid.</p>";
echo "<p>Transaction ID: <strong>$refId</strong></p>";
echo "<a href='user_dashboard.php'>Go back to dashboard</a>";
