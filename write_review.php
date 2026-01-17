<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get booking_id from URL for the initial form load
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Correct the names to match the form
    $review_text = $_POST['review'] ?? ''; // Matches <textarea name="review">
    $rating = intval($_POST['rating'] ?? 0);
    $user_id = $_SESSION['user_id'];
    $booking_id = intval($_POST['booking_id'] ?? 0);

    // 2. Fetch missing vendor/service details to ensure database integrity
    $check = $conn->prepare("SELECT service_id, vendor_id FROM bookings WHERE booking_id = ? AND user_id = ?");
    $check->bind_param("ii", $booking_id, $user_id);
    $check->execute();
    $booking_data = $check->get_result()->fetch_assoc();

    if ($booking_data) {
        $service_id = $booking_data['service_id'];
        $vendor_id = $booking_data['vendor_id'];

        // 3. Insert into reviews table (matching your 'review_text' column name)
        $stmt = $conn->prepare("INSERT INTO reviews (booking_id, user_id, service_id, vendor_id, review_text, rating, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        // Data Types: i = integer, s = string. Order: i (booking), i (user), i (service), i (vendor), s (text), i (rating)
        $stmt->bind_param("iiiisi", $booking_id, $user_id, $service_id, $vendor_id, $review_text, $rating);

        if ($stmt->execute()) {
            header("Location: user_dashboard.php?msg=ReviewSaved");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Error: Booking not found or you don't have permission.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Write Review</title>
</head>
<body>
    <div style="max-width: 500px; margin: 20px auto; font-family: sans-serif;">
        <form method="POST">
            <h3>Write Review</h3>
            
            <label>Your Feedback:</label><br>
            <textarea name="review" rows="5" style="width: 100%;" required placeholder="How was the service?"></textarea><br><br>
            
            <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
            
            <label>Rating (1-5):</label><br>
            <input type="number" name="rating" min="1" max="5" value="5" required><br><br>
            
            <button type="submit" style="padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer;">
                Submit Review
            </button>
            <a href="user_dashboard.php" style="margin-left: 10px;">Cancel</a>
        </form>
    </div>
</body>
</html>