<?php
session_start();
include 'db.php';

// Set header to return JSON (required for your JS fetch)
header('Content-Type: application/json');

// Check session
if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'users') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Handle JSON input (from Fetch) or POST input (from Modal Form)
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Use null coalescing to check both JSON and standard POST
    $booking_id  = intval($data['booking_id'] ?? $_POST['booking_id'] ?? 0);
    $rating      = intval($data['rating'] ?? $_POST['rating'] ?? 0);
    $review_text = trim($data['review'] ?? $data['review_text'] ?? $_POST['review_text'] ?? '');

    // 2. Validation
    if (!$booking_id || !$rating || empty($review_text)) {
        echo json_encode(["success" => false, "message" => "Please provide a rating and review text."]);
        exit;
    }

    // 3. Fetch booking to ensure it exists and belongs to this user
    $stmt = $conn->prepare("SELECT service_id, vendor_id, status FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        echo json_encode(["success" => false, "message" => "Booking record not found."]);
        exit;
    }

    // Optional: Only allow reviews for completed bookings
    if (strtolower($booking['status']) !== 'completed') {
        echo json_encode(["success" => false, "message" => "You can only review completed services."]);
        exit;
    }

    $service_id = intval($booking['service_id']);
    $vendor_id  = intval($booking['vendor_id']);

    // 4. Check if a review already exists for this booking
    $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // UPDATE EXISTING REVIEW
        $stmt = $conn->prepare("UPDATE reviews SET rating = ?, review_text = ? WHERE review_id = ? AND user_id = ?");
        $stmt->bind_param("isii", $rating, $review_text, $existing['review_id'], $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Review updated successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        }
    } else {
        // INSERT NEW REVIEW
        $stmt = $conn->prepare("INSERT INTO reviews (booking_id, service_id, user_id, vendor_id, rating, review_text, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiiiis", $booking_id, $service_id, $user_id, $vendor_id, $rating, $review_text);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Review submitted successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        }
    }
    $stmt->close();

} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>