<?php
session_start();
include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'users') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');

    if (!$booking_id || !$rating || empty($review_text)) {
        $_SESSION['msg'] = "⚠ Please provide a rating and review text.";
        header("Location: user_dashboard.php?active_page=bookings");
        exit;
    }

    // Fetch booking
    $stmt = $conn->prepare("SELECT service_id, vendor_id, status FROM bookings WHERE booking_id=? AND user_id=?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        $_SESSION['msg'] = "⚠ Booking not found.";
    } elseif (strtolower($booking['status']) !== 'completed') {
        $_SESSION['msg'] = "⚠ You can only review completed services.";
    } else {
        $service_id = $booking['service_id'];
        $vendor_id = $booking['vendor_id'];

        // Check if review exists
        $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id=? AND user_id=?");
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            // Update
            $stmt = $conn->prepare("UPDATE reviews SET rating=?, review_text=? WHERE review_id=? AND user_id=?");
            $stmt->bind_param("isii", $rating, $review_text, $existing['review_id'], $user_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['msg'] = "✅ Review updated successfully!";
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO reviews (booking_id, service_id, user_id, vendor_id, rating, review_text) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiis", $booking_id, $service_id, $user_id, $vendor_id, $rating, $review_text);
            $stmt->execute();
            $stmt->close();
            $_SESSION['msg'] = "✅ Review submitted successfully!";
        }
    }

    header("Location: user_dashboard.php?active_page=bookings");
    exit;
}
header("Location: user_dashboard.php");
exit;
?>
