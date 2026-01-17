<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
if(!$user_id) {
    die("⚠ You must be logged in.");
}

// Fetch all bookings of the user
$my_bookings = $conn->query("
   SELECT b.booking_id, b.booking_date, b.booking_time, b.status, b.payment_status,
          b.vendor_id, b.service_id,
          s.category, s.description, 
          v.name AS vendor_name, v.location,
          vs.price,
          r.review_id, r.rating, r.review_text
   FROM bookings b
   JOIN services s ON b.service_id = s.service_id
   JOIN vendors v ON b.vendor_id = v.vendor_id
   JOIN vendor_services vs ON b.vendor_id = vs.vendor_id AND b.service_id = vs.service_id
   LEFT JOIN reviews r ON b.booking_id = r.booking_id AND r.user_id = $user_id
   WHERE b.user_id = $user_id
   ORDER BY b.booking_date DESC, b.booking_time DESC
")->fetch_all(MYSQLI_ASSOC);

if(!empty($my_bookings)){
    foreach($my_bookings as $b){

        // Determine display status
        $status = $b['status'] ?? 'pending';
        if(strtolower($b['payment_status'] ?? '') === 'paid'){
            $status_display = 'Paid';
        } else {
            $status_display = ucfirst($status);
        }

        echo '<div class="booking-card" id="booking-'.$b['booking_id'].'">';
        echo '<h3>'.htmlspecialchars($b['category']).'</h3>';
        echo '<p>'.htmlspecialchars($b['description']).'</p>';
        echo '<p>Vendor: '.htmlspecialchars($b['vendor_name']).' ('.htmlspecialchars($b['location']).')<br>';
        echo 'Date: '.htmlspecialchars($b['booking_date']).' | Time: '.htmlspecialchars($b['booking_time']).'<br>';
        echo '<strong>Price: ₹'.htmlspecialchars($b['price']).'</strong></p>';
        echo '<span class="status '.htmlspecialchars(strtolower($status_display)).'">'.htmlspecialchars($status_display).'</span>';

        // =========================
        // Review section
        // =========================
        if(strtolower($status) === 'completed'){
            if(empty($b['review_id'])){
                echo '<form method="post" class="review-form">';
                echo '<input type="hidden" name="booking_id" value="'.$b['booking_id'].'">';
                echo '<label>Rating:</label>';
                echo '<select name="rating" required>
                        <option value="">Select</option>
                        <option value="5">⭐⭐⭐⭐⭐</option>
                        <option value="4">⭐⭐⭐⭐</option>
                        <option value="3">⭐⭐⭐</option>
                        <option value="2">⭐⭐</option>
                        <option value="1">⭐</option>
                      </select>';
                echo '<textarea name="review_text" placeholder="Write your review..." required></textarea>';
                echo '<button type="submit">Submit Review</button>';
                echo '</form>';
            } else {
                $stars = str_repeat('★', $b['rating']) . str_repeat('☆', 5 - $b['rating']);
                echo '<div class="submitted-review" id="review-'.$b['review_id'].'">';
                echo '<div class="rating">'.$stars.' <span>'.number_format($b['rating'],1).'</span></div>';
                echo '<div class="review-text">'.nl2br(htmlspecialchars($b['review_text'])).'</div>';
                echo '</div>';
            }
        }

        // =========================
        // Pay via eSewa button (only if not paid)
        // =========================
        if(strtolower($b['payment_status'] ?? '') !== 'paid'){

            // Make PID unique with booking ID + timestamp
            $pid = 'booking_'.$b['booking_id'].'_'.time();

            // eSewa endpoint - change to sandbox or live as needed
            $esewa_url = "https://esewa.com.np/epay/main"; // live
            // $esewa_url = "https://uat.esewa.com.np/epay/main"; // sandbox (if available)

            echo '<form method="POST" action="'.$esewa_url.'" class="esewa-form">';
            echo '<input type="hidden" name="tAmt" value="'.$b['price'].'">';      // Total amount
            echo '<input type="hidden" name="amt" value="'.$b['price'].'">';       // Service amount
            echo '<input type="hidden" name="txAmt" value="0">';                  // Tax
            echo '<input type="hidden" name="psc" value="0">';                     // Service charge
            echo '<input type="hidden" name="pdc" value="0">';                     // Delivery charge
            echo '<input type="hidden" name="scd" value="YOUR_MERCHANT_CODE">';   // Merchant code
            echo '<input type="hidden" name="pid" value="'.$pid.'">';              // Unique booking PID
            echo '<input type="hidden" name="su" value="http://localhost/sewahub/esewa_success.php">'; // Success URL
            echo '<input type="hidden" name="fu" value="http://localhost/sewahub/payment_fail.php">';    // Fail URL
            echo '<button type="submit">💳 Pay via eSewa</button>';
            echo '</form>';

            // Optional: Insert pending payment into payments table if not already
            $check = $conn->query("SELECT * FROM payments WHERE booking_id=".$b['booking_id']." AND status='pending' AND pay_method='esewa'");
            if($check->num_rows === 0){
                $stmt = $conn->prepare("
                    INSERT INTO payments (booking_id, user_id, vendor_id, service_id, amount, pay_method, status, pay_date)
                    VALUES (?, ?, ?, ?, ?, 'esewa', 'pending', NOW())
                ");
                $stmt->bind_param("iiiii", $b['booking_id'], $user_id, $b['vendor_id'], $b['service_id'], $b['price']);
                $stmt->execute();
                $stmt->close();
            }
        }

        echo '</div>'; // booking-card
    }
} else {
    echo '<p>No bookings found.</p>';
}
