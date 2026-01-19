<?php
session_start();
include 'db.php';

// ✅ Session check
if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'users') {
    header("Location: login.php");
    exit();
}
// =========================
// EDIT REVIEW (AJAX)
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_review'])) {

    $review_id = (int)$_POST['review_id'];
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare(
        "UPDATE reviews 
         SET rating=?, review_text=? 
         WHERE review_id=? AND user_id=?"
    );
    $stmt->bind_param("isii", $rating, $review_text, $review_id, $user_id);

    echo json_encode(['success' => $stmt->execute()]);
    $stmt->close();
    exit;
}

// =========================
// DELETE REVIEW
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {

    $review_id = (int)$_POST['review_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id=? AND user_id=?");
    $stmt->bind_param("ii", $review_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$user_id = $_SESSION['user_id'];
// Handle new review submission
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['booking_id'], $_POST['rating'], $_POST['review_text'])
    && !isset($_POST['edit_review'])
) {
    $booking_id = (int)$_POST['booking_id'];
    $rating = (int)$_POST['rating'];
    $review_text = $conn->real_escape_string($_POST['review_text']);

    $stmt = $conn->prepare("SELECT vendor_id, service_id, status FROM bookings WHERE booking_id=? AND user_id=?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if(!$booking || strtolower($booking['status']) !== 'completed') {
        $msg = "⚠ Invalid booking or not completed";
    } else {
        $vendor_id = $booking['vendor_id'];
        $service_id = $booking['service_id'];

        // Check existing review
        $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id=? AND user_id=?");
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if($existing){
            $stmt = $conn->prepare("UPDATE reviews SET rating=?, review_text=? WHERE review_id=? AND user_id=?");
            $stmt->bind_param("isii", $rating, $review_text, $existing['review_id'], $user_id);
            $stmt->execute();
            $stmt->close();
            $msg = "✅ Review updated successfully!";
        } else {
            $stmt = $conn->prepare("INSERT INTO reviews (booking_id, service_id, user_id, vendor_id, rating, review_text, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiiiss", $booking_id, $service_id, $user_id, $vendor_id, $rating, $review_text);
            $stmt->execute();
            $stmt->close();
            $msg = "✅ Review submitted successfully!";
        }
    }
}


$user_id = $_SESSION['user_id'];
$active_page = $active_page ?? 'dashboard';


// ✅ Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// ✅ Fetch distinct vendor locations from database
$locations = [];
$locResult = $conn->query("SELECT DISTINCT location FROM vendors WHERE is_approved=1 ORDER BY location ASC");
if ($locResult && $locResult->num_rows > 0) {
    while ($row = $locResult->fetch_assoc()) {
        $locations[] = $row['location'];
    }
}

// ✅ Fetch distinct service categories from services table
$services = [];
$servicesResult = $conn->query("SELECT DISTINCT category FROM services ORDER BY category ASC");
if($servicesResult && $servicesResult->num_rows > 0) {
    while($row = $servicesResult->fetch_assoc()) {
        $services[] = $row['category'];
    }
}

// ✅ Dashboard stats
$total_services = $conn->query("SELECT COUNT(*) AS count FROM bookings WHERE user_id = $user_id")->fetch_assoc()['count'] ?? 0;
$completed_services = $conn->query("SELECT COUNT(*) AS count FROM bookings WHERE user_id = $user_id AND status='completed'")->fetch_assoc()['count'] ?? 0;
$total_earnings = $conn->query("SELECT SUM(amount) AS total FROM payments WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$avg_rating = $conn->query("SELECT AVG(rating) AS avg FROM reviews WHERE user_id = $user_id")->fetch_assoc()['avg'] ?? 0;

// ✅ Recent bookings
$bookings = $conn->query("
    SELECT b.booking_id, b.booking_date, b.booking_time, b.status,
           s.category AS service_name,
           v.name AS vendor_name,
           vs.price
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN vendors v ON b.vendor_id = v.vendor_id
    JOIN vendor_services vs ON b.vendor_id = vs.vendor_id AND b.service_id = vs.service_id
    WHERE b.user_id = $user_id
    ORDER BY b.booking_date DESC, b.booking_time DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ✅ Payments
$payments = $conn->query("SELECT * FROM payments WHERE user_id = $user_id ORDER BY pay_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);


// ✅ Fetch user reviews
$reviews = $conn->query("
 SELECT 
    r.review_id,
    r.rating,
    r.review_text,
    r.created_at,
    s.category AS service_name,
    v.name AS vendor_name,
    v.profile_photo
FROM reviews r
JOIN bookings b ON r.booking_id = b.booking_id
JOIN services s ON b.service_id = s.service_id
JOIN vendors v ON b.vendor_id = v.vendor_id
WHERE r.user_id = $user_id
GROUP BY r.booking_id
ORDER BY r.created_at DESC

")->fetch_all(MYSQLI_ASSOC);
// Fetch completed bookings without review
$completed_no_review = $conn->query("
    SELECT b.booking_id, b.booking_date, b.booking_time,
           s.category AS service_name, v.name AS vendor_name, v.vendor_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN vendors v ON b.vendor_id = v.vendor_id
    LEFT JOIN reviews r ON r.booking_id = b.booking_id
    WHERE b.user_id = $user_id
      AND b.status = 'completed'
      AND r.review_id IS NULL
    ORDER BY b.booking_date DESC, b.booking_time DESC
")->fetch_all(MYSQLI_ASSOC);


// ✅ Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name  = $conn->real_escape_string($_POST['last_name']);
    $email      = $conn->real_escape_string($_POST['email']);
    $phone      = $conn->real_escape_string($_POST['phone']);
    $address    = $conn->real_escape_string($_POST['address']);

$profile_photo = $user['profile_photo']; // keep old photo

// ✅ Handle image upload
if (!empty($_FILES['profile_photo']['name'])) {

    // Relative path instead of absolute
    $upload_dir = "uploads/users/"; 

    // Create folder if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array(strtolower($ext), $allowed)) {

        $new_name = "user_" . $user_id . "_" . time() . "." . $ext;
        $target = $upload_dir . $new_name; // save relative path

        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target)) {
            $profile_photo = $target; // store relative path in DB
        } else {
            $msg = "<p class='error-msg'>❌ Failed to upload photo.</p>";
        }
    } else {
        $msg = "<p class='error-msg'>❌ Invalid image type.</p>";
    }
}


    $update = $conn->prepare(
        "UPDATE users 
         SET first_name=?, last_name=?, email=?, phone=?, address=?, profile_photo=? 
         WHERE user_id=?"
    );

    $update->bind_param(
        "ssssssi",
        $first_name,
        $last_name,
        $email,
        $phone,
        $address,
        $profile_photo,
        $user_id
    );

    if ($update->execute()) {
        $msg = "<p class='success-msg'>✅ Profile updated successfully!</p>";

        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        $msg = "<p class='error-msg'>❌ Failed to update profile.</p>";
    }

    $update->close();
}


// ✅ Handle booking insert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_service'])) {

    $vendor_id  = (int) $_POST['vendor_id'];
    $service_id = (int) $_POST['service_id'];
    $date       = $_POST['date'];
    $time       = $_POST['time'];

  try {
   $conn->begin_transaction();

// Get daily limit
$limitStmt = $conn->prepare("SELECT daily_limit FROM vendors WHERE vendor_id=? AND is_approved=1");
$limitStmt->bind_param("i", $vendor_id);
$limitStmt->execute();
$daily_limit = (int)$limitStmt->get_result()->fetch_assoc()['daily_limit'];
$limitStmt->close();

// Count daily bookings
$countStmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM bookings 
     WHERE vendor_id=? AND booking_date=? AND status IN ('pending','confirmed','accepted')"
);
$countStmt->bind_param("is", $vendor_id, $date);
$countStmt->execute();
$dailyCount = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

if ($dailyCount >= $daily_limit) {
    throw new Exception("Daily limit reached");
}

// ⚠ Time conflict check
$timeCheckStmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM bookings 
     WHERE vendor_id=? AND service_id=? AND booking_date=? AND booking_time=? 
     AND status IN ('pending','confirmed','accepted')"
);
$timeCheckStmt->bind_param("iiss", $vendor_id, $service_id, $date, $time);
$timeCheckStmt->execute();
$timeCheck = $timeCheckStmt->get_result()->fetch_assoc()['total'] ?? 0;
$timeCheckStmt->close();

if ($timeCheck > 0) {
    throw new Exception("This vendor is already booked for this service at the selected time.");
}

// Insert booking
$insert = $conn->prepare(
    "INSERT INTO bookings (user_id, vendor_id, service_id, booking_date, booking_time, status)
     VALUES (?, ?, ?, ?, ?, 'pending')"
);
$insert->bind_param("iiiss", $user_id, $vendor_id, $service_id, $date, $time);
$insert->execute();
$booking_id = $insert->insert_id;
$insert->close();

// Notification
$notif = $conn->prepare(
    "INSERT INTO notifications (recipient_type, recipient_id, booking_id, message)
     VALUES ('admin', 0, ?, ?)"
);
$msg_text = "New booking #$booking_id by {$user['first_name']} {$user['last_name']}";
$notif->bind_param("is", $booking_id, $msg_text);
$notif->execute();
$notif->close();

$conn->commit();
$msg = "<p class='success-msg'>✅ Booking confirmed successfully!</p>";
$active_page = "bookings";


} catch (mysqli_sql_exception $e) {

    $conn->rollback();

    if ($e->getCode() == 1062) {
        $msg = "<p class='error-msg'>⚠ Vendor already booked at this time.</p>";
    } else {
        $msg = "<p class='error-msg'>❌ Booking failed.</p>";
    }

} catch (Exception $e) {

    $conn->rollback();

    if ($e->getMessage() === "Daily limit reached") {
        $msg = "<p class='error-msg'>⚠ Vendor has reached daily booking limit.</p>";
    } else {
        $msg = "<p class='error-msg'>❌ ".$e->getMessage()."</p>";
    }
}

}


// ✅ Handle search service
$search_results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_service'])) {
    $category = $conn->real_escape_string($_POST['category']);
    $location = $conn->real_escape_string($_POST['location']);
    $date = $conn->real_escape_string($_POST['date']);
    $time = $conn->real_escape_string($_POST['time']);

    // Existing query to fetch services
    $sql = "SELECT s.service_id, s.category, s.description,
                   v.vendor_id, v.name AS vendor_name, v.location, v.skills,v.phone,v.profile_photo,
                   vs.price, vs.Available_From, vs.Available_To,
                   IFNULL(AVG(r.rating),0) AS avg_rating,
                   COUNT(r.review_id) AS total_reviews,
                   v.daily_limit
            FROM services s
            JOIN vendor_services vs ON s.service_id = vs.service_id
            JOIN vendors v ON vs.vendor_id = v.vendor_id
            LEFT JOIN reviews r ON r.service_id = s.service_id AND r.vendor_id = v.vendor_id
            WHERE s.category LIKE '%$category%'
              AND v.location LIKE '%$location%'
              AND v.is_approved = 1
            GROUP BY s.service_id, v.vendor_id";

    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['date'] = $date;
            $row['time'] = $time;

            // Check how many bookings exist for this vendor on the selected date
            $check_count = $conn->prepare("SELECT COUNT(*) AS booked_count 
                                           FROM bookings 
                                           WHERE vendor_id=? AND booking_date=? AND status IN ('pending','confirmed')");
            $check_count->bind_param("is", $row['vendor_id'], $date);
            $check_count->execute();
            $count_result = $check_count->get_result();
            $booked_count = $count_result->fetch_assoc()['booked_count'] ?? 0;
            $check_count->close();

            $remaining_slots = max(0, $row['daily_limit'] - $booked_count);
            $row['is_available'] = ($remaining_slots > 0);
            $row['remaining_slots'] = $remaining_slots;

            $search_results[] = $row;
        }
    }

    $active_page = "book-service";
}
// ✅ Fetch all bookings for My Bookings
$my_bookings = $conn->query("
SELECT 
    b.booking_id, b.booking_date, b.booking_time, b.status,
    b.payment_status, 
    b.vendor_id, b.service_id,
    s.category, s.description,
    v.name AS vendor_name, v.location,
    COALESCE(vs.price, 0) AS price
FROM bookings b
JOIN services s ON b.service_id = s.service_id
JOIN vendors v ON b.vendor_id = v.vendor_id
LEFT JOIN vendor_services vs 
     ON b.vendor_id = vs.vendor_id 
    AND b.service_id = vs.service_id
WHERE b.user_id = $user_id
    
ORDER BY b.booking_date DESC, b.booking_time DESC
")->fetch_all(MYSQLI_ASSOC);




// Handle review delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $review_id = (int)$_POST['review_id'];

    $stmt = $conn->prepare(
        "DELETE FROM reviews WHERE review_id=? AND user_id=?"
    );
    $stmt->bind_param("ii", $review_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $active_page = "reviews";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sewa Hub Dashboard </title>
    <link rel="stylesheet" href="css/user_dashboard.css">
</head>

<body>
    <div class="dashboard-container">

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo-section">
                    <img src="logo.png" alt="Sewa Hub" class="logo-img">
                    <span class="app-name">Sewa Hub</span>
                </div>
                <button class="toggle-btn" id="toggleBtn">☰</button>
            </div>
            <nav class="nav-menu">
                <button class="nav-item <?= !isset($active_page) || $active_page==='dashboard' ? 'active' : '' ?>"
                    data-page="dashboard">📊 Dashboard</button>
                <button class="nav-item <?= isset($active_page) && $active_page==='book-service' ? 'active' : '' ?>"
                    data-page="book-service">📋 Book Service</button>
                <button class="nav-item <?= isset($active_page) && $active_page==='bookings' ? 'active' : '' ?>"
                    data-page="bookings">
                    📖 My Bookings
                </button>

                <button class="nav-item <?= isset($active_page) && $active_page==='reviews' ? 'active' : '' ?>"
                    data-page="reviews">
                    ⭐ Reviews
                </button>

                <button class="nav-item <?= isset($active_page) && $active_page==='payments' ? 'active' : '' ?>"
                    data-page="payments">
                    💳 Payments
                </button>

            </nav>

        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="header clean-header">
                <div class="header-left">
                    <h2 class="page-title-text">
                        <?= ucfirst($active_page ?? 'Dashboard') ?>
                    </h2>
                </div>

                <div class="header-profile">
                    <button type="button" class="profile-btn" data-page="profile">
                        <div class="header-avatar">
                            <img src="<?= !empty($user['profile_photo']) 
        ? htmlspecialchars($user['profile_photo']) 
        : 'assets/default-avatar.png' ?>" class="header-avatar-img" alt="User">
                        </div>

                    </button>
                </div>
            </header>



            <main class="content">

                <!-- Dashboard Page -->
                <div class="page <?= !isset($active_page) || $active_page==='dashboard' ? 'active' : '' ?>"
                    id="dashboard-page">
                    <div class="page-header">
                        <h1>Welcome, <?= htmlspecialchars($user['first_name']) ?>!</h1>
                        <p>Here’s your overview</p>
                    </div>

                    <div class="kpi-grid">

                        <div class="kpi-card">
                            <div class="kpi-top">
                                <span class="kpi-icon">📦</span>
                                <span class="kpi-title">Total Bookings</span>
                            </div>
                            <div class="kpi-value"><?= $total_services ?></div>
                            <div class="kpi-sub">All-time bookings</div>
                        </div>

                        <div class="kpi-card success">
                            <div class="kpi-top">
                                <span class="kpi-icon">✅</span>
                                <span class="kpi-title">Completed</span>
                            </div>
                            <div class="kpi-value"><?= $completed_services ?></div>
                            <div class="kpi-sub">Services completed</div>
                        </div>

                        <div class="kpi-card warning">
                            <div class="kpi-top">
                                <span class="kpi-icon">💸</span>
                                <span class="kpi-title">Total Spent</span>
                            </div>
                            <div class="kpi-value">NRS <?= number_format($total_earnings) ?></div>
                            <div class="kpi-sub">Lifetime spending</div>
                        </div>

                        <div class="kpi-card info">
                            <div class="kpi-top">
                                <span class="kpi-icon">⭐</span>
                                <span class="kpi-title">Avg Rating</span>
                            </div>
                            <div class="kpi-value"><?= number_format($avg_rating,1) ?></div>
                            <div class="kpi-sub">Based on your reviews</div>
                        </div>

                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Bookings</h3>
                        </div>
                        <div class="card-content">
                            <?php foreach ($bookings as $b): ?>
                            <div class="booking-item">
                                <div class="booking-info">
                                    <h4><?= htmlspecialchars($b['service_name'] ?? 'Service') ?></h4>
                                    <p>
                                        <?= htmlspecialchars($b['vendor_name'] ?? 'Vendor') ?> •
                                        <?= htmlspecialchars($b['booking_date']) ?> •
                                        <?= htmlspecialchars($b['booking_time']) ?>
                                    </p>

                                    <span
                                        class="status <?= htmlspecialchars($b['status']) ?>"><?= ucfirst($b['status']) ?></span>
                                </div>
                                <div class="booking-price">₹<?= $b['price'] ?? 0 ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Profile Page -->
                <div class="page <?= isset($active_page) && $active_page==='profile' ? 'active' : '' ?>"
                    id="profile-page">
                    <?= $msg ?? '' ?>
                    <div class="profile-grid">
                        <div class="card profile-card">
                            <div class="card-header">
                                <h3>Personal Information</h3>
                            </div>
                            <div class="profile-top">
                                <div class="avatar">
                                    <img src="<?= !empty($user['profile_photo']) 
        ? htmlspecialchars($user['profile_photo']) 
        : 'assets/default-avatar.png' ?>" class="profile-avatar-img" alt="Profile">
                                </div>

                                <div class="user-info">
                                    <h2><?= htmlspecialchars($user['first_name']." ".$user['last_name']) ?></h2>
                                    <span class="verified">✔ Verified</span>
                                </div>
                            </div>
                            <div class="card-content">
                                <form method="post" enctype="multipart/form-data" class="profile-form">
                                    <div class="profile-photo-section">
                                        <div class="profile-photo-wrapper">
                                            <img src="<?= !empty($user['profile_photo']) 
                ? htmlspecialchars($user['profile_photo']) 
                : 'assets/default-avatar.png' ?>" class="profile-photo" alt="Profile Photo">
                                        </div>

                                        <label class="upload-btn">
                                            📷 Change Photo
                                            <input type="file" name="profile_photo" accept="image/*" hidden>
                                        </label>
                                    </div>

                                    <div class="form-row">
                                        <div class="input-group">
                                            <label>First Name</label>
                                            <input type="text" name="first_name"
                                                value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                        </div>
                                        <div class="input-group">
                                            <label>Last Name</label>
                                            <input type="text" name="last_name"
                                                value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="input-group">
                                            <label>Email</label>
                                            <input type="email" name="email"
                                                value="<?= htmlspecialchars($user['email']) ?>" required>
                                        </div>
                                        <div class="input-group">
                                            <label>Phone</label>
                                            <input type="text" name="phone"
                                                value="<?= htmlspecialchars($user['phone']) ?>">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="input-group full-width">
                                            <label>Address</label>
                                            <input type="text" name="address"
                                                value="<?= htmlspecialchars($user['address']) ?>">
                                        </div>
                                    </div>
                                    <button type="submit" name="update_profile">💾 Update Profile</button>
                                </form>
                                <!-- ✅ Logout Button moved here -->
                                <div class="logout-profile">
                                    <a href="logout.php" class="logout-btn">🚪 Logout</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Book Service Page -->
                <div class="page <?= isset($active_page) && $active_page==='book-service' ? 'active' : '' ?>"
                    id="book-service-page">

                    <div class="booking-hero">
                        <h1>Book a Service</h1>
                        <p>Find trusted & verified professionals near you</p>
                    </div>

                    <form method="post" class="modern-booking-form">
                        <div class="form-grid">

                            <div class="form-group">
                                <label>Service</label>
                                <select name="category" required>
                                    <option value="">Select service</option>
                                    <?php foreach($services as $service): ?>
                                    <option value="<?= htmlspecialchars($service) ?>"><?= htmlspecialchars($service) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Location</label>
                                <select name="location" required>
                                    <option value="">Select location</option>
                                    <?php foreach($locations as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Date</label>
                                <input type="date" name="date" required>
                            </div>

                            <div class="form-group">
                                <label>Time</label>
                                <input type="time" name="time" required>
                            </div>

                        </div>

                        <button type="submit" name="search_service" class="primary-btn">
                            🔍 Search Professionals
                        </button>
                    </form>

                </div>

                <div class="search-results">
                    <?php if (!empty($search_results)): ?>
                    <h2>Available Service Vendors</h2>
                    <?php foreach ($search_results as $row): ?>
                    <div class="vendor-card">

                        <!-- Vendor Top -->
                        <div class="vendor-top">
                            <img src="<?= !empty($row['profile_photo']) 
                            ? htmlspecialchars($row['profile_photo']) 
                            : 'assets/default-avatar.png' ?>" alt="Vendor Photo" class="vendor-photo">

                            <div class="vendor-basic">
                                <h3><?= htmlspecialchars($row['vendor_name']) ?></h3>
                                <span class="service-tag"><?= htmlspecialchars($row['category']) ?></span>

                                <div class="vendor-rating">
                                    ⭐ <?= number_format($row['avg_rating'] ?? 0,1) ?>
                                    <span>(<?= $row['total_reviews'] ?? 0 ?> reviews)</span>
                                </div>
                                <p class="vendor-phone">📞 <?= htmlspecialchars($row['phone'] ?? 'N/A') ?></p>
                            </div>
                        </div>

                        <p class="vendor-location">📍 <?= htmlspecialchars($row['location']) ?></p>
                        <p class="vendor-price">₹<?= $row['price'] ?? 0 ?>/service</p>

                        <div class="vendor-actions">
                            <?php if (!empty($row['is_available'])): ?>
                            <button type="button" class="book-btn" data-vendor-id="<?= $row['vendor_id'] ?>"
                                data-service-id="<?= $row['service_id'] ?>"
                                data-vendor-name="<?= htmlspecialchars($row['vendor_name']) ?>"
                                data-service-name="<?= htmlspecialchars($row['category']) ?>"
                                data-price="<?= $row['price'] ?>" data-date="<?= $row['date'] ?>"
                                data-time="<?= $row['time'] ?>">
                                📌 Book Now
                            </button>
                            <?php else: ?>
                            <button class="book-btn disabled" disabled>❌ Not Available</button>
                            <?php endif; ?>
                            <!-- WhatsApp button -->
                            <?php if (!empty($row['phone'])): ?>
                            <?php 
                // Remove non-numeric characters for WhatsApp URL
                $whatsapp_number = preg_replace('/\D+/', '', $row['phone']);
                // Optional default message
                $message = urlencode("Hello, I am interested in your service: " . $row['category']);
            ?>
                            <a href="https://wa.me/<?= $whatsapp_number ?>?text=<?= $message ?>" target="_blank"
                                class="whatsapp-btn">
                                🟢 WhatsApp
                            </a>
                            <?php endif; ?>
                        </div>

                    </div>
                    <?php endforeach; ?>
                    <?php elseif (isset($active_page) && $active_page==='book-service'): ?>
                    <p>No providers found for this search.</p>
                    <?php endif; ?>
                </div>



                <!-- Booking Modal -->
                <div id="bookingModal" class="modal">
                    <div class="modal-content booking-modal">
                        <span class="close-btn">&times;</span>
                        <h2>Book Your Service</h2>

                        <div class="booking-details">
                            <p><strong>Vendor:</strong> <span id="modalVendorDisplay"></span></p>
                            <p><strong>Service:</strong> <span id="modalServiceDisplay"></span></p>
                            <p><strong>Price:</strong> ₹<span id="modalPriceDisplay"></span> / hour</p>
                        </div>

                        <form id="bookingForm" class="booking-form">
                            <div class="form-row">
                                <label>Date</label>
                                <input type="date" id="modalDate" name="date" required>
                            </div>

                            <div class="form-row">
                                <label>Time</label>
                                <input type="time" id="modalTime" name="time" required>
                            </div>
                            <input type="hidden" id="modalVendorId" name="vendor_id">
                            <input type="hidden" id="modalServiceId" name="service_id">
                            <input type="hidden" id="modalServiceName" name="service_name">
                            <p id="bookingMessage" class="booking-message"></p>
                            <button type="submit" class="btn-confirm">Confirm Booking</button>
                        </form>
                    </div>
                </div>

                <!-- My Bookings Page -->
                <div class="page <?= isset($active_page) && $active_page==='bookings' ? 'active' : '' ?>"
                    id="bookings-page">
                    <h1 class="page-title">My Bookings</h1>

                    <?php if (!empty($my_bookings)): ?>
                    <div class="bookings-grid">
                        <?php foreach ($my_bookings as $b): ?>
                        <?php
                // Normalize status
                $status = strtolower(trim($b['status'] ?? ''));
                $payment = strtolower(trim($b['payment_status'] ?? 'unpaid'));
                $price = $b['price'] ?? 0;
            ?>
                        <div class="booking-card">

                            <!-- Booking Header -->
                            <div class="booking-header">
                                <h3><?= htmlspecialchars($b['category'] ?? 'Service') ?></h3>
                                <span class="booking-status <?= $status ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </div>

                            <!-- Booking Info -->
                            <div class="booking-info">
                                <p><?= htmlspecialchars($b['description'] ?? '') ?></p>
                                <p>
                                    Vendor: <?= htmlspecialchars($b['vendor_name'] ?? 'N/A') ?>
                                    (<?= htmlspecialchars($b['location'] ?? 'N/A') ?>)<br>
                                    Date: <?= htmlspecialchars($b['booking_date']) ?> | Time:
                                    <?= htmlspecialchars($b['booking_time']) ?><br>
                                    <strong>Price: ₹<?= number_format($price, 2) ?></strong>
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="booking-actions">
                                <?php if ($payment !== 'paid'): ?>
                                <form action="start_esewa_payment.php" method="POST">
                                    <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                                    <button type="submit" style="width:100%;background:#16a34a;color:#fff;
               padding:12px;border:none;border-radius:6px;
               font-size:14px;cursor:pointer;">
                                        💳 Pay via eSewa
                                    </button>
                                </form>
                                <?php else: ?>
                                <span style="color:green;font-weight:bold;">✅ Paid</span>
                                <?php endif; ?>

                                <!-- Review Form (show for all completed bookings, ignore payment) -->
                                <?php if ($status === 'completed'): ?>
                                <form method="post" name="submit_review" class="review-form">
    <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
    
    <label>Rating:</label>
    <select name="rating" required>
        <option value="">Select</option>
        <option value="5">⭐⭐⭐⭐⭐</option>
        <option value="4">⭐⭐⭐⭐</option>
        <option value="3">⭐⭐⭐</option>
        <option value="2">⭐⭐</option>
        <option value="1">⭐</option>
    </select>

    <textarea name="review_text" placeholder="Write your review..." required></textarea>

    <button type="submit">Submit Review</button>
</form>



                                <?php endif; ?>

                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="empty-state">No bookings found.</p>
                    <?php endif; ?>
                </div>


                <!-- Payments Page -->
                <div class="page" id="payments-page">
                    <h1 class="page-title">Payment History</h1>

                    <?php if (!empty($payments)): ?>
                    <div class="payments-grid">
                        <?php foreach ($payments as $p): ?>
                        <div class="payment-card">
                            <div class="payment-header">
                                <div class="payment-service"><?= htmlspecialchars($p['service_name'] ?? 'Service') ?>
                                </div>
                                <div class="payment-date"><?= date("M d, Y H:i", strtotime($p['pay_date'])) ?></div>
                            </div>

                            <div class="payment-body">
                                <span class="payment-status completed">Completed</span>
                                <div class="payment-amount">₹<?= number_format($p['amount'],2) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="empty-state">No payment records found.</p>
                    <?php endif; ?>
                </div>

                <!-- Reviews Page -->
                 


                 <!-- Add Review Section for Completed Bookings -->
                  
<?php if (!empty($completed_no_review)): ?>
<div class="add-review-section">
    <h2>Give a Review for Completed Services</h2>

    <?php foreach ($completed_no_review as $c): ?>
    <div class="review-card">
        <div class="review-header">
            <div class="review-info">
                <h3><?= htmlspecialchars($c['vendor_name']) ?></h3>
                <span class="service-badge"><?= htmlspecialchars($c['service_name']) ?></span>
            </div>
            <div class="review-date">
                <?= htmlspecialchars($c['booking_date']) ?> <?= htmlspecialchars($c['booking_time']) ?>
            </div>
        </div>

        <form method="post" class="review-form">
            <input type="hidden" name="booking_id" value="<?= $c['booking_id'] ?>">

            <label>Rating:</label>
            <select name="rating" required>
                <option value="">Select</option>
                <option value="5">⭐⭐⭐⭐⭐</option>
                <option value="4">⭐⭐⭐⭐</option>
                <option value="3">⭐⭐⭐</option>
                <option value="2">⭐⭐</option>
                <option value="1">⭐</option>
            </select>

            <textarea name="review_text" placeholder="Write your review..." required></textarea>

            <button type="submit" name="submit_review">Submit Review</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

                <div class="page <?= isset($active_page) && $active_page==='reviews' ? 'active' : '' ?>"
                    id="reviews-page">
                    <h1 class="page-title">My Reviews</h1>

                    <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $r): ?>
                    <div class="review-card" id="review-<?= $r['review_id'] ?>">
                        <!-- ✅ Added ID -->

                        <div class="review-header">
                            <img src="<?= !empty($r['profile_photo']) 
        ? htmlspecialchars($r['profile_photo']) 
        : 'assets/default-avatar.png' ?>" class="vendor-avatar" alt="<?= htmlspecialchars($r['vendor_name']) ?>">


                            <div class="review-info">
                                <h3><?= htmlspecialchars($r['vendor_name']) ?></h3>
                                <span class="service-badge"><?= htmlspecialchars($r['service_name']) ?></span>

                                <div class="rating">
                                    <?= str_repeat("★", (int)$r['rating']) ?>
                                    <?= str_repeat("☆", 5 - (int)$r['rating']) ?>
                                    <span><?= number_format($r['rating'],1) ?></span>
                                </div>
                            </div>

                            <div class="review-date">
                                <?= date("M d, Y", strtotime($r['created_at'])) ?>
                            </div>
                        </div>

                        <p class="review-text">
                            <?= nl2br(htmlspecialchars($r['review_text'])) ?>
                        </p>

                        <!-- ACTION BUTTONS -->
                        <div class="review-actions">
                            <!-- Edit Button -->
                            <button class="edit-btn" onclick="openEditModal(
                        <?= $r['review_id'] ?>,
                        <?= $r['rating'] ?>,
                        `<?= htmlspecialchars($r['review_text'], ENT_QUOTES) ?>`
                    )">
                                ✏ Edit
                            </button>

                            <!-- Delete Button -->
                            <form method="post" onsubmit="return confirm('Delete this review?');">
                                <input type="hidden" name="delete_review" value="1">
                                <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                                <button type="submit" class="delete-btn">🗑 Delete</button>
                            </form>
                        </div>

                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p class="empty-state">You have not written any reviews yet.</p>
                    <?php endif; ?>
                </div>

                    </div>
                </div>

                <script src="js/user_dashboard.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
          <div id="editReviewModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeEditModal()">&times;</span>

        <h2>Edit Review</h2>

        <input type="hidden" id="edit_review_id">

        <label>Rating</label>
        <select id="edit_rating">
            <option value="5">⭐⭐⭐⭐⭐</option>
            <option value="4">⭐⭐⭐⭐</option>
            <option value="3">⭐⭐⭐</option>
            <option value="2">⭐⭐</option>
            <option value="1">⭐</option>
        </select>

        <textarea id="edit_review_text"></textarea>

        <button onclick="saveEditReview()">💾 Save</button>
    </div>
</div>
     
</body>
<script>
function openEditModal(id, rating, text) {
    document.getElementById('edit_review_id').value = id;
    document.getElementById('edit_rating').value = rating;
    document.getElementById('edit_review_text').value = text;

    document.getElementById('editReviewModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editReviewModal').style.display = 'none';
}

function saveEditReview() {
    const review_id = document.getElementById('edit_review_id').value;
    const rating = document.getElementById('edit_rating').value;
    const review_text = document.getElementById('edit_review_text').value;

    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `edit_review=1&review_id=${review_id}&rating=${rating}&review_text=${encodeURIComponent(review_text)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Review updated');
            location.reload();
        } else {
            alert('Failed to update review');
        }
    });
}
</script>

</html>
