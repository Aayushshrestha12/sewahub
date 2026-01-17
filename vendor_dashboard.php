<?php
session_start();
include 'db.php';
// Redirect if not logged in as vendor
if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'vendors') {
    header("Location: login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];
//  Fetch vendor info
$vendorQuery = $conn->prepare("SELECT * FROM vendors WHERE vendor_id=?");
$vendorQuery->bind_param("i", $vendor_id);
$vendorQuery->execute();
$vendor = $vendorQuery->get_result()->fetch_assoc();
$vendorQuery->close();

if (!$vendor) {
    header("Location: login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    /* ===== PHOTO UPLOAD ===== */
    if ($_POST['action'] === 'upload_photo' && isset($_FILES['profile_photo'])) {

        $file = $_FILES['profile_photo'];

        if ($file['error'] !== 0) {
            $_SESSION['error'] = "Upload failed";
            header("Location: vendor_dashboard.php");
            exit();
        }

        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = "Invalid image type";
            header("Location: vendor_dashboard.php");
            exit();
        }

        $uploadDir = "uploads/vendors/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $photoPath = $uploadDir . "vendor_" . $vendor_id . "." . $ext;

        move_uploaded_file($file['tmp_name'], $photoPath);

        $stmt = $conn->prepare(
            "UPDATE vendors SET profile_photo=? WHERE vendor_id=?"
        );
        $stmt->bind_param("si", $photoPath, $vendor_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = "Photo updated";
        header("Location: vendor_dashboard.php");
        exit();
    }

    /* ===== PROFILE INFO UPDATE ===== */
    if ($_POST['action'] === 'update_profile') {

        $name   = !empty($_POST['name'])   ? trim($_POST['name'])   : $vendor['name'];
        $email  = !empty($_POST['email'])  ? trim($_POST['email'])  : $vendor['email'];
        $phone  = !empty($_POST['phone'])  ? trim($_POST['phone'])  : $vendor['phone'];
        $skills = !empty($_POST['skills']) ? trim($_POST['skills']) : $vendor['skills'];

        $stmt = $conn->prepare("
            UPDATE vendors 
            SET name=?, email=?, phone=?, skills=?
            WHERE vendor_id=?
        ");
        $stmt->bind_param("ssssi", $name, $email, $phone, $skills, $vendor_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = "Profile updated";
        header("Location: vendor_dashboard.php");
        exit();
    }
}


// Count today's bookings
$todayBookingsQuery = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE vendor_id = ?
      AND DATE(booking_date) = ?
      AND status IN ('accepted', 'completed')
");

$todayBookingsQuery->bind_param("is", $vendor_id, $today);
$todayBookingsQuery->execute();
$todayBookingsResult = $todayBookingsQuery->get_result();
$todayBookings = $todayBookingsResult->fetch_assoc()['total'] ?? 0;
$todayBookingsQuery->close();

// Remaining slots
$remainingSlots = max(0, ($vendors['daily_limit'] ?? 5) - $todayBookings);
// Fetch next 7 days bookings count
$upcomingDays = [];
for($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    
   $countQuery = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM bookings 
    WHERE vendor_id = ? 
      AND DATE(booking_date) = ?  
      AND status IN ('accepted','completed')
");
$countQuery->bind_param("is", $vendor_id, $date);
$countQuery->execute();
$result = $countQuery->get_result();
$bookedCount = $result->fetch_assoc()['total'] ?? 0;
$countQuery->close();


    $upcomingDays[$date] = [
        'booked' => $bookedCount,
        'remaining' => max(0, ($vendor['daily_limit'] ?? 5) - $bookedCount),
        'full' => ($bookedCount >= ($vendor['daily_limit'] ?? 5))
    ];
}
// earnings query
$earningsQuery = $conn->prepare("
    SELECT SUM(amount) AS total
    FROM payments
    WHERE vendor_id = ?
");
$earningsQuery->bind_param("i", $vendor_id);
$earningsQuery->execute();
$earningsResult = $earningsQuery->get_result();
$totalEarnings = $earningsResult->fetch_assoc()['total'] ?? 0;
$earningsQuery->close();
//
$earningsQuery = $conn->prepare("
    SELECT 
        SUM(IF(DATE(pay_date)=CURDATE(), amount, 0)) AS today,
        SUM(IF(MONTH(pay_date)=MONTH(CURDATE()) 
           AND YEAR(pay_date)=YEAR(CURDATE()), amount, 0)) AS month
    FROM payments
    WHERE vendor_id = ?
      AND status = 'paid'
");
$earningsQuery->bind_param("i", $vendor_id);
$earningsQuery->execute();
$earn = $earningsQuery->get_result()->fetch_assoc();



// query for avg rating
$ratingQuery = $conn->prepare("
    SELECT AVG(rating) AS avg_rating
    FROM reviews
    WHERE vendor_id = ?
");
$ratingQuery->bind_param("i", $vendor_id);
$ratingQuery->execute();
$ratingResult = $ratingQuery->get_result();
$avgRating = $ratingResult->fetch_assoc()['avg_rating'] ?? 0;
$ratingQuery->close();

// Fetch bookings with customer and service info
$bookingsQuery = $conn->prepare("
    SELECT b.booking_id, b.booking_date, b.booking_time, b.status,
           u.first_name AS customer_first_name, u.last_name AS customer_last_name,
           s.category AS service_name
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN services s ON b.service_id = s.service_id
   WHERE b.vendor_id = ?
  AND b.status IN ('accepted', 'completed')
ORDER BY b.booking_date DESC, b.booking_time DESC

");
$bookingsQuery->bind_param("i", $vendor_id);
$bookingsQuery->execute();
$bookingsResult = $bookingsQuery->get_result();
$bookingsQuery->close();
// for bookings trend
$trend = [];
for ($i = 6; $i >= 0; $i--) {
    $trend[date('Y-m-d', strtotime("-$i days"))] = 0;
}

$trendQuery = $conn->prepare("
    SELECT booking_date, COUNT(*) total
    FROM bookings
    WHERE vendor_id = ?
      AND booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      AND status IN ('accepted','completed')
    GROUP BY booking_date
");
$trendQuery->bind_param("i", $vendor_id);
$trendQuery->execute();
$res = $trendQuery->get_result();

while ($row = $res->fetch_assoc()) {
    $trend[$row['booking_date']] = $row['total'];
}

// Fetch vendor services
$servicesQuery = $conn->prepare("
    SELECT s.service_id, s.category, s.description, vs.price, vs.available_from, vs.available_to, vs.id
    FROM services s
    JOIN vendor_services vs ON s.service_id = vs.service_id
    WHERE vs.vendor_id = ?
");



$servicesQuery->bind_param("i", $vendor_id);
$servicesQuery->execute();
$servicesResult = $servicesQuery->get_result();
$servicesQuery->close();

// Fetch payments
$paymentsQuery = $conn->prepare("
    SELECT 
        p.payment_id,
        p.amount,
        p.status,
        p.pay_date,
        p.pay_method,
        u.first_name,
        u.last_name,
        s.category AS service_name
    FROM payments p
    JOIN users u ON p.user_id = u.user_id
    JOIN services s ON p.service_id = s.service_id
    WHERE p.vendor_id = ?
    ORDER BY p.pay_date DESC
");
$paymentsQuery->bind_param("i", $vendor_id);
$paymentsQuery->execute();
$paymentsResult = $paymentsQuery->get_result();
$paymentsQuery->close();



// Fetch reviews
$reviewsQuery = $conn->prepare("
    SELECT r.review_id, r.rating, r.review_text, r.created_at,
           u.first_name AS user_first_name, u.last_name AS user_last_name,
           s.category AS service_name
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN services s ON r.service_id = s.service_id
    WHERE r.vendor_id = ?
    ORDER BY r.created_at DESC
");
$reviewsQuery->bind_param("i", $vendor_id);
$reviewsQuery->execute();
$reviewsResult = $reviewsQuery->get_result();
$reviewsQuery->close();
// query for alerts 
$alerts = [];

if ($remainingSlots <= 1) {
    $alerts[] = "⚠️ Almost fully booked today";
}
if ($todayBookings == 0) {
    $alerts[] = "📉 No bookings today yet";
}
if ($reviewsResult->num_rows > 0) {
    $alerts[] = "⭐ New review received";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewa Hub - Vendor Dashboard</title>
    <link rel="stylesheet" href="css/vendor_dashboard.css">
</head>

<body>
    <div class="dashboard-container">

        <!-- Sidebar -->
     <aside class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <div class="logo">
            <img src="logo.png" class="logo-img">
            <span class="logo-text">SewaHub</span>
        </div>

        <button class="toggle-btn" id="sidebarToggle">☰</button>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item active"><a href="#" data-section="dashboard">📊 Dashboard</a></li>
            <li class="nav-item"><a href="#" data-section="services">🛠 My Services</a></li>
            <li class="nav-item"><a href="#" data-section="availability">📅 Availability</a></li>
            <li class="nav-item"><a href="#" data-section="daily_limit">🔢 Daily Limit</a></li>
            <li class="nav-item"><a href="#" data-section="bookings">📖 Bookings</a></li>
            <li class="nav-item"><a href="#" data-section="payments">💳 Payments</a></li>
            <li class="nav-item"><a href="#" data-section="reviews">⭐ Reviews</a></li>

            <?php if($vendor['is_approved'] == 0): ?>
                <li class="nav-item"><a href="#" data-section="profile_verification">Profile Verification</a></li>
            <?php endif; ?>
        </ul>
    </nav>

</aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-right">
            <div class="profile-avatar-container">
    <!-- Avatar & dropdown toggle -->
    <img src="<?= !empty($vendor['profile_photo']) ? htmlspecialchars($vendor['profile_photo']) : 'assets/default-avatar.png' ?>"
         alt="Profile" class="profile-avatar" id="profileAvatar">

    <!-- Dropdown -->
    <div class="profile-dropdown" id="profileDropdown">
        <!-- Profile Info Form -->
        <form method="post" id="profileForm">
            <input type="hidden" name="action" value="update_profile">
            <label>Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($vendor['name'] ?? '') ?>">

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($vendor['email'] ?? '') ?>">

            <label>Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($vendor['phone'] ?? '') ?>">

            <label>Skills</label>
            <textarea name="skills"><?= htmlspecialchars($vendor['skills'] ?? '') ?></textarea>

            <button type="submit" class="btn">Save</button>
        </form>

        <!-- Change Photo Button -->
        <form method="post" enctype="multipart/form-data" id="uploadPhotoForm">
            <input type="hidden" name="action" value="upload_photo">
            <input type="file" name="profile_photo" id="profilePhotoInput" style="display:none;" accept="image/*">
            <button type="button" id="changePhotoBtn" class="btn">Change Photo</button>
        </form>

        <!-- Logout -->
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</div>



                </div>
            </header>

            <div class="content">

<script>
const profileAvatar = document.getElementById('profileAvatar');
const fileInput = document.getElementById('profilePhotoInput');
const form = document.getElementById('uploadPhotoForm');

profileAvatar.addEventListener('click', () => {
    fileInput.click();
});

fileInput.addEventListener('change', () => {
    form.submit(); // auto-submit after selecting photo
});
</script>

<section id="vendor-profile-card" class="content-section">
    <h2>My Profile</h2>
    <div class="profile-card">
        <!-- Left: Avatar -->
        <div class="profile-avatar-wrapper">
            
           
        </div>

        <!-- Right: Info -->
        <div class="profile-info">
            <h3><?= htmlspecialchars($vendor['name'] ?? 'Unnamed Vendor') ?></h3>
            <p class="vendor-email">📧 <?= htmlspecialchars($vendor['email'] ?? '-') ?></p>
            <p class="vendor-phone">📱 <?= htmlspecialchars($vendor['phone'] ?? '-') ?></p>
            <p class="vendor-skills"><strong>Skills:</strong> <?= htmlspecialchars($vendor['skills'] ?? 'Not specified') ?></p>
            <p class="vendor-experience"><strong>Experience:</strong> <?= htmlspecialchars($vendor['experience'] ?? '0') ?> years</p>
            <p class="vendor-rating">⭐ Average Rating: <?= number_format($avgRating ?? 0, 1) ?>/5</p>

            <div class="profile-actions">
                <a href="#profile_verification-section" class="btn btn-primary">Edit Profile</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</section>

                <!-- Messages -->
                <?php if(isset($_SESSION['message'])): ?>
                <div class="success-msg"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
                <?php endif; ?>
                <?php if(isset($_SESSION['error'])): ?>
                <div class="error-msg"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <!-- Dashboard Section -->
                <section id="dashboard-section" class="content-section active">
                    <h2>Dashboard Overview</h2>
                    <?php if ($alerts): ?>
                    <div class="alert-box">
                        <?php foreach ($alerts as $alert): ?>
                        <div class="alert"><?= $alert ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <span>Today's Bookings</span>
                            <h3><?= $todayBookings ?></h3>
                        </div>

                        <div class="kpi-card">
                            <span>Remaining Slots Today</span>
                            <h3><?= $remainingSlots ?></h3>
                        </div>

                        <div class="kpi-card">
                            <span>Total Earnings</span>
                            <h3>NRS <?= number_format($totalEarnings ?? 0) ?></h3>
                        </div>

                        <div class="kpi-card">
                            <span>Average Rating</span>
                            <h3><?= number_format($avgRating ?? 0, 1) ?> ⭐</h3>
                        </div>
                        <div class="kpi-card">
                            <span>Earnings Today</span>
                            <h3>NRS <?= number_format($earn['today'] ?? 0) ?></h3>
                        </div>

                        <div class="kpi-card">
                            <span>This Month</span>
                            <h3>NRS <?= number_format($earn['month'] ?? 0) ?></h3>
                        </div>

                    </div>
                    <h3 style="margin-top:25px;">Upcoming Availability (Next 7 Days)</h3>

                    <div class="availability-grid">
                        <?php foreach ($upcomingDays as $date => $day): ?>
                        <div class="day-box <?= $day['full'] ? 'full' : 'open' ?>">
                            <div class="day-name"><?= date('D', strtotime($date)) ?></div>
                            <div class="day-date"><?= $date ?></div>

                            <?php if ($day['full']): ?>
                            <div class="day-status full">FULL</div>
                            <?php else: ?>
                            <div class="day-status open"><?= $day['remaining'] ?> slots</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="trend-chart">
                        <?php
$max = max($trend) ?: 1;
foreach ($trend as $date => $count):
    $height = ($count / $max) * 100;
?>
                        <div class="bar">
                            <div class="bar-fill" style="height: <?= $height ?>%"></div>
                            <span><?= date('D', strtotime($date)) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>



                </section>

                <!-- Profile Verification / Vendor Application -->
                <?php if($vendor['is_approved'] == 0): ?>
                <section id="profile_verification-section" class="content-section">
                    <h2>Profile Verification / Vendor Application</h2>
                    <p class="info-text">You are not yet a verified vendor. Submit your profile to admin for approval.
                    </p>

                    <form method="POST" action="vendor_application.php" class="profile-verification-form">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($vendor['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($vendor['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($vendor['phone']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Skills</label>
                            <textarea name="skills"><?= htmlspecialchars($vendor['skills']) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" value="<?= htmlspecialchars($vendor['location']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Experience</label>
                            <textarea name="experience"><?= htmlspecialchars($vendor['experience']) ?></textarea>
                        </div>
                        <button type="submit" class="btn submit-btn">Submit Profile</button>
                    </form>
                </section>
                <?php endif; ?>

                <!-- Services Section -->
                <section id="services-section" class="content-section">
                    <h2>My Services</h2>
                    <button id="addServiceBtn" class="btn add-btn">+ Add Service</button>

                    <table class="services-table" id="servicesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Service</th>
                                <th>Description</th>
                                <th>Price (NRS)</th>
                                <th>Available From</th>
                                <th>Available To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
        $count = 1;
        while($service = $servicesResult->fetch_assoc()):
            $from = ($service['available_from'] && $service['available_from'] != '0000-00-00 00:00:00')
                    ? date('Y-m-d\TH:i', strtotime($service['available_from']))
                    : '';
            $to   = ($service['available_to'] && $service['available_to'] != '0000-00-00 00:00:00')
                    ? date('Y-m-d\TH:i', strtotime($service['available_to']))
                    : '';
        ?>
                            <tr>
                                <td><?= $count++; ?></td>
                                <td><?= htmlspecialchars($service['category']); ?></td>
                                <td><?= htmlspecialchars($service['description']); ?></td>
                                <td><?= htmlspecialchars($service['price']); ?></td>
                                <td><?= $from ? date('Y-m-d H:i', strtotime($service['available_from'])) : ''; ?></td>
                                <td><?= $to ? date('Y-m-d H:i', strtotime($service['available_to'])) : ''; ?></td>
                                <td>
                                    <button class="editServiceBtn btn" data-id="<?= $service['id']; ?>"
                                        data-service_id="<?= $service['service_id']; ?>"
                                        data-price="<?= htmlspecialchars($service['price']); ?>"
                                        data-available_from="<?= $from ?>" data-available_to="<?= $to ?>">
                                        Edit
                                    </button>
                                    <button class="deleteServiceBtn btn"
                                        data-id="<?= $service['id']; ?>">Delete</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </section>
                <!-- Availability Section -->
                <section id="availability-section" class="content-section">
                    <h2>Service Availability</h2>

                    <table class="services-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Service</th>
                                <th>Available From</th>
                                <th>Available To</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
        $count = 1;
        $availabilityServices = $conn->prepare("
            SELECT vs.id, s.category, vs.available_from, vs.available_to
            FROM vendor_services vs
            JOIN services s ON s.service_id = vs.service_id
            WHERE vs.vendor_id = ?
        ");
        $availabilityServices->bind_param("i", $vendor_id);
        $availabilityServices->execute();
        $availabilityResult = $availabilityServices->get_result();
        ?>

                            <?php while($row = $availabilityResult->fetch_assoc()): ?>
                            <tr>
                                <form method="POST" action="update_availability.php">
                                    <td><?= $count++ ?></td>
                                    <td><?= htmlspecialchars($row['category']) ?></td>

                                    <td>
                                        <input type="datetime-local" name="available_from"
                                            value="<?= $row['available_from'] ? date('Y-m-d\TH:i', strtotime($row['available_from'])) : '' ?>"
                                            required>
                                    </td>

                                    <td>
                                        <input type="datetime-local" name="available_to"
                                            value="<?= $row['available_to'] ? date('Y-m-d\TH:i', strtotime($row['available_to'])) : '' ?>"
                                            required>
                                    </td>

                                    <td>
                                        <input type="hidden" name="vendor_service_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn">Save</button>
                                    </td>
                                </form>
                            </tr>
                            <?php endwhile; ?>

                        </tbody>
                    </table>
                </section>
                <section id="daily_limit-section" class="content-section">
                    <h2>Daily Booking Limit</h2>

                    <div class="limit-card">
                        <div class="limit-info">
                            <div class="limit-icon">🔢</div>
                            <div class="limit-text">
                                <p>Maximum bookings per day</p>
                                <h3><?= htmlspecialchars($vendor['daily_limit'] ?? 5) ?></h3>
                            </div>
                        </div>

                        <form method="POST" action="update_daily_limit.php" class="limit-form">
                            <label for="daily_booking_limit">Set New Limit</label>
                            <div class="limit-input-group">
                                <input type="number" id="daily_booking_limit" name="daily_booking_limit" min="1"
                                    max="50" value="<?= htmlspecialchars($vendor['daily_limit'] ?? 5) ?>" required>
                                <button type="submit" class="btn save-btn">💾 Save</button>
                            </div>
                        </form>

                        <p class="info-text">
                            ⚠️ Once your daily limit is reached, new bookings will be automatically rejected.
                        </p>
                    </div>
                </section>




                <!-- Add/Edit Service Modal -->
                <div id="serviceModal" class="modal">
                    <div class="modal-content">
                        <span class="close-btn" id="closeServiceModal">&times;</span>
                        <h2 id="serviceModalTitle">Add Service</h2>

                        <form id="serviceForm" method="POST" action="save_service.php">
                            <input type="hidden" name="vendor_service_id" id="vendor_service_id">

                            <label>Service</label>
                            <select name="service_id" id="service_id_select" required>
                                <option value="">Select Service</option>
                                <?php
                $allServices = $conn->query("SELECT service_id, category FROM services");
                while($s = $allServices->fetch_assoc()):
                ?>
                                <option value="<?= $s['service_id'] ?>"><?= htmlspecialchars($s['category']) ?></option>
                                <?php endwhile; ?>
                            </select>

                            <label>Price</label>
                            <input type="number" name="price" id="price" step="0.01" required>

                            <label>Available From</label>
                            <input type="datetime-local" name="available_from" id="available_from" required>

                            <label>Available To</label>
                            <input type="datetime-local" name="available_to" id="available_to" required>

                            <button type="submit">Save</button>
                        </form>
                    </div>
                </div>


                <!-- Bookings Section -->
                <section id="bookings-section" class="content-section">
                    <h2>Bookings</h2>
                    <div class="table-container">
                        <table class="bookings-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($booking = $bookingsResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['customer_first_name'] . ' ' . $booking['customer_last_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['booking_date'] . ' ' . $booking['booking_time']); ?>
                                    </td>
                                    <td class="booking-status <?php echo $booking['status']; ?>">
                                        <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] === 'accepted'): ?>
                                        <button class="markCompletedBtn btn"
                                            data-booking-id="<?= $booking['booking_id'] ?>">
                                            Mark Completed
                                        </button>

                                        <?php elseif ($booking['status'] === 'completed'): ?>
                                        <span class="completed-label">Completed</span>

                                        <?php else: ?>
                                        <span class="disabled-label">Waiting Admin Approval</span>
                                        <?php endif; ?>
                                    </td>

                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Payments Section -->
               <section id="payments-section" class="content-section">
    <div class="payments-header">
        <h2>Payments</h2>
        <span class="payments-subtitle">Recent transactions & earnings</span>
    </div>

    <div class="payments-card">
        <table class="payments-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Txn ID</th>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>

                <?php if($paymentsResult->num_rows === 0): ?>
                <tr>
                    <td colspan="7" class="no-data">No payments recorded yet</td>
                </tr>
                <?php endif; ?>

                <?php while($payment = $paymentsResult->fetch_assoc()): 
                    $payDate = strtotime($payment['pay_date']);
                    $displayDate = ($payDate && $payment['pay_date'] != '0000-00-00')
                        ? date('d M Y', $payDate)
                        : 'N/A';

                   

                    $method = strtolower($payment['pay_method'] ?? '');
                    $status = strtolower($payment['status']);
                ?>
                <tr>
                    <td><?= $displayDate ?></td>

                    <td class="txn-id"><?= $txnId ?></td>

                    <td><?= htmlspecialchars($payment['first_name'].' '.$payment['last_name']) ?></td>

                    <td><?= htmlspecialchars($payment['service_name']) ?></td>

                    <td>
                        <span class="payment-method <?= $method ?>">
                            <?= ucfirst($payment['pay_method']) ?>
                        </span>
                    </td>

                    <td class="amount">NRS <?= number_format($payment['amount'], 2) ?></td>

                    <td>
                        <span class="payment-status <?= $status ?>">
                            <?= ucfirst($payment['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>

            </tbody>
        </table>
    </div>
</section>

              

                <!-- Reviews Section -->
                <section id="reviews-section" class="content-section">
    <h2 class="section-title">Customer Reviews</h2>

    <div class="reviews-container">
        <?php while($review = $reviewsResult->fetch_assoc()): ?>
        <div class="review-card">

            <div class="review-header">
                <div class="review-user">
                    <div class="avatar">
                        <?php echo strtoupper(substr($review['user_first_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <strong>
                            <?php echo htmlspecialchars($review['user_first_name'] . ' ' . $review['user_last_name']); ?>
                        </strong>
                        <span class="service-name">
                            <?php echo htmlspecialchars($review['service_name']); ?>
                        </span>
                    </div>
                </div>

                <div class="review-date">
                    <?php echo date("M d, Y", strtotime($review['created_at'])); ?>
                </div>
            </div>

            <div class="review-rating">
                <?php
                for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $review['rating'] ? '⭐' : '☆';
                }
                ?>
            </div>

            <p class="review-text">
                <?php echo htmlspecialchars($review['review_text']); ?>
            </p>

        </div>
        <?php endwhile; ?>
    </div>
</section>



            </div>
        </main>
    </div>

    <script src="js/vendor_dashboard.js"></script>
</body>

