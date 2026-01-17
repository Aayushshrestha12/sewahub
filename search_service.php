<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$category = $conn->real_escape_string($_POST['category'] ?? '');
$location = $conn->real_escape_string($_POST['location'] ?? '');
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';

if (!$category || !$location || !$date || !$time) {
    echo json_encode([]);
    exit();
}

// Prevent past date/time search
$selectedDateTime = strtotime("$date $time");
if ($selectedDateTime < time()) {
    echo json_encode([]);
    exit();
}

// ✅ Correct query using vendors and vendor_service
$sql = "
SELECT s.service_id, s.category, s.description,
       v.vendor_id AS provider_id, v.name AS provider_name, v.location, v.skills,v.profile_photo
       vs.price, vs.Available_From, vs.Available_To,
       IFNULL(AVG(r.rating),0) AS avg_rating,
       COUNT(r.review_id) AS total_reviews,
       v.dialy_limit
FROM services s
JOIN vendor_services vs ON s.service_id = vs.service_id
JOIN vendors v ON vs.vendor_id = v.vendor_id
LEFT JOIN reviews r ON r.service_id = s.service_id AND r.vendor_id = v.vendor_id
WHERE s.category LIKE '%$category%'
  AND v.location LIKE '%$location%'
  AND v.is_approved = 1
GROUP BY s.service_id, v.vendor_id
";

$result = $conn->query($sql);
$providers = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Check vendor availability
        $check = $conn->prepare("SELECT * FROM bookings WHERE vendor_id=? AND booking_date=? AND booking_time=? AND status IN ('pending','confirmed')");
        $check->bind_param("iss", $row['provider_id'], $date, $time);
        $check->execute();
        $avail_result = $check->get_result();

        $row['is_available'] = ($avail_result->num_rows === 0);
        $row['date'] = $date;
        $row['time'] = $time;

        $providers[] = $row;
        $check->close();
    }
}

echo json_encode($providers);
