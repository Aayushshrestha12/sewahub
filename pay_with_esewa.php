<?php
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'];
$booking_id = intval($_POST['booking_id']);

// Fetch booking details
$stmt = $conn->prepare("SELECT vs.price, b.vendor_id 
                        FROM bookings b 
                        JOIN vendor_service vs ON b.vendor_id=vs.vendor_id AND b.service_id=vs.service_id 
                        WHERE b.booking_id=? AND b.user_id=?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$booking = $res->fetch_assoc();

$amount = $booking['price'];
$vendor_id = $booking['vendor_id'];

// Insert payment record
$stmt2 = $conn->prepare("INSERT INTO payments (booking_id,user_id,vendor_id,amount,payment_method,status) 
                         VALUES (?,?,?,?, 'online','pending')");
$stmt2->bind_param("iiid", $booking_id, $user_id, $vendor_id, $amount);
$stmt2->execute();
$payment_id = $stmt2->insert_id;

// Esewa settings
$merchant_code = "EPAYTEST"; // change to your live merchant code
$success_url = "http://localhost/demo/esewa_success.php";
$failure_url = "http://localhost/demo/esewa_failed.php";

?>

<form id="esewaForm" action="https://uat.esewa.com.np/epay/main" method="POST">
  <input type="hidden" name="amt" value="<?= $amount ?>">
  <input type="hidden" name="pdc" value="0">
  <input type="hidden" name="psc" value="0">
  <input type="hidden" name="txAmt" value="0">
  <input type="hidden" name="tAmt" value="<?= $amount ?>">
  <input type="hidden" name="pid" value="<?= $payment_id ?>">
  <input type="hidden" name="scd" value="<?= $merchant_code ?>">
  <input type="hidden" name="su" value="<?= $success_url ?>?pid=<?= $payment_id ?>">
  <input type="hidden" name="fu" value="<?= $failure_url ?>?pid=<?= $payment_id ?>">
</form>

<script>
document.getElementById("esewaForm").submit();
</script>
