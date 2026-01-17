<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit();
}

$user_id       = $_SESSION['user_id'];
$vendor_id     = intval($_POST['vendor_id'] ?? 0);
$service_id    = intval($_POST['service_id'] ?? 0);
$time          = $conn->real_escape_string($_POST['time'] ?? '');
$note          = $conn->real_escape_string($_POST['note'] ?? '');
$payment_method = $_POST['payment_method'] ?? 'cash'; // cash / online

if (!$vendor_id || !$service_id) {
    echo json_encode(["success" => false, "message" => "Invalid booking request"]);
    exit();
}

// Fetch service price (assuming vendor_service table has price column)
$service_query = $conn->prepare("
    SELECT price 
    FROM vendor_services 
    WHERE vendor_id=? AND service_id=? 
    LIMIT 1
");
$service_query->bind_param("ii", $vendor_id, $service_id);
$service_query->execute();
$service_result = $service_query->get_result();
$service = $service_result->fetch_assoc();
$price = $service['price'] ?? 0;
$service_query->close();

// Insert booking
$stmt = $conn->prepare("INSERT INTO bookings (user_id, vendor_id, service_id, booking_date, booking_time, note, status) 
                        VALUES (?, ?, ?, CURDATE(), ?, ?, 'pending')");
$stmt->bind_param("iiiss", $user_id, $vendor_id, $service_id, $time, $note);

if ($stmt->execute()) {
    $booking_id = $stmt->insert_id;

    // Insert into payments table
    $status = ($payment_method === "cash") ? "pending" : "initiated";
    $stmt2 = $conn->prepare("INSERT INTO payments (booking_id,user_id,vendor_id,amount,payment_method,status) 
                             VALUES (?,?,?,?,?,?)");
    $stmt2->bind_param("iiidss", $booking_id, $user_id, $vendor_id, $price, $payment_method, $status);
    $stmt2->execute();
    $payment_id = $stmt2->insert_id;

    if ($payment_method === "online") {
        // eSewa integration (UAT / Test environment)
        $merchant_code = "EPAYTEST"; // replace with your live code when ready
        $success_url   = "http://localhost/demo/esewa_success.php";
        $failure_url   = "http://localhost/demo/esewa_failed.php";

        $esewa_url = "https://uat.esewa.com.np/epay/main?" . http_build_query([
            'amt' => $price,
            'pdc' => 0,
            'psc' => 0,
            'txAmt' => 0,
            'tAmt' => $price,
            'pid' => $payment_id,
            'scd' => $merchant_code,
            'su'  => $success_url . "?pid=" . $payment_id,
            'fu'  => $failure_url . "?pid=" . $payment_id
        ]);

        echo json_encode([
            "success"  => true,
            "redirect" => $esewa_url
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "Booking placed successfully with Cash on Delivery"
        ]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}
$stmt->close();
