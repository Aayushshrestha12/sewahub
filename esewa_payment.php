<?php
session_start();
include 'db.php';

if (!isset($_POST['booking_id'], $_POST['amount'])) {
    die("Invalid request");
}

$booking_id = intval($_POST['booking_id']);
$amount     = floatval($_POST['amount']);

// Merchant credentials
$MERCHANT_CODE = 'YOUR_MERCHANT_CODE'; // Replace with your eSewa code
$SUCCESS_URL   = 'http://yourdomain.com/esewa_success.php';
$FAIL_URL      = 'http://yourdomain.com/esewa_fail.php';

// Generate a unique transaction ID if needed
$pid = "booking_{$booking_id}_" . time();

// Insert payment record with status pending
$stmt = $conn->prepare("INSERT INTO payments (booking_id, user_id, vendor_id, service_id, amount, status, pay_method, pay_date) VALUES (?, ?, ?, ?, ?, 'pending', 'esewa', NOW())");
$stmt->bind_param(
    "iiiii",
    $booking_id,
    $_SESSION['user_id'],
    $_POST['vendor_id'],
    $_POST['service_id'],
    $amount
);
$stmt->execute();
$stmt->close();
?>

<!-- Redirect to eSewa -->
<form id="esewaForm" method="POST" action="https://esewa.com.np/epay/main">
    <input value="<?= $amount ?>" name="amt">
    <input value="0" name="psc">
    <input value="0" name="pdc">
    <input value="<?= $amount ?>" name="tAmt">
    <input value="<?= $pid ?>" name="pid">
    <input value="<?= $MERCHANT_CODE ?>" name="scd">
    <input value="<?= $SUCCESS_URL ?>?pid=<?= $pid ?>" type="hidden" name="su">
    <input value="<?= $FAIL_URL ?>" type="hidden" name="fu">
</form>

<script>
    document.getElementById('esewaForm').submit();
</script>
