<?php
session_start();
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['loginType'] !== 'admins') {
    header("Location: login.php");
    exit();
}

// Fetch admin info
$stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id=? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();
// ===== DASHBOARD KPI DATA =====

// Total users
$totalUsers = $conn->query("SELECT COUNT(*) total FROM users")
                   ->fetch_assoc()['total'];

// New users today
$newUsersToday = $conn->query("
    SELECT COUNT(*) total 
    FROM users 
    WHERE DATE(created_at) = CURDATE()
")->fetch_assoc()['total'];
// Total approved providers
$totalvendors = $conn->query("SELECT COUNT(*) as total FROM vendors WHERE is_approved=1")
                       ->fetch_assoc()['total'];

// New approved providers today
$newvendorsToday = $conn->query("
    SELECT COUNT(*) as total 
    FROM vendors 
    WHERE is_approved=1 AND DATE(created_at) = CURDATE()
")->fetch_assoc()['total'];


// Total bookings
$totalBookings = $conn->query("SELECT COUNT(*) total FROM bookings")
                      ->fetch_assoc()['total'];

// Bookings today
$bookingsToday = $conn->query("
    SELECT COUNT(*) total 
    FROM bookings 
    WHERE DATE(booking_date) = CURDATE()
")->fetch_assoc()['total'];

// Revenue today
$revenueToday = $conn->query("
    SELECT SUM(amount) total 
    FROM payments 
    WHERE status='success' AND DATE(pay_date) = CURDATE()
")->fetch_assoc()['total'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewa Hub Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin_dashboard.css">

</head>

<body>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-chevron-left" id="toggleIcon"></i>
            </div>
            <div class="sidebar-header">
                <div class="logo">
                    <img src="logo.png" alt="Sewa Hub Logo" class="logo-img">
                    <span class="logo-text">Sewa Hub</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item active" data-tab="dashboard">
                        <a href="#" class="nav-link">
                            <i class="fas fa-chart-bar"></i>
                            <span class="nav-text">📊 Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item" data-tab="manage-user">
                        <a href="#" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span class="nav-text">👥 Manage User</span>
                        </a>
                    </li>
                    <li class="nav-item" data-tab="manage-provider">
                        <a href="#" class="nav-link">
                            <i class="fas fa-building"></i>
                            <span class="nav-text">🧑‍🔧 Manage Vendor</span>
                        </a>
                    </li>
                    <li class="nav-item" data-tab="manage-service">
                        <a href="#" class="nav-link">
                            <i class="fas fa-wrench"></i>
                            <span class="nav-text">🛠 Manage Service</span>
                        </a>
                    </li>
                    <li class="nav-item" data-tab="bookings">
                        <a href="#" class="nav-link">
                            <i class="fas fa-calendar"></i>
                            <span class="nav-text">📖 Bookings</span>
                        </a>
                    </li>
                    <li class="nav-item" data-tab="payments">
                        <a href="#" class="nav-link">
                            <i class="fas fa-credit-card"></i>
                            <span class="nav-text">💳 Payments</span>
                        </a>
                    </li>
                </ul>
            </nav>

        </div>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Top Bar -->
            <div class="top-bar">
              <div class="profile-avatar" onclick="toggleProfile()">
<?php 
if(!empty($admin['profile_photo']) && file_exists("uploads/admins/".$admin['profile_photo'])) {
    echo "<img src='uploads/admins/{$admin['profile_photo']}' alt='Profile' style='width:100%; height:100%; border-radius:50%; object-fit:cover;'>";
} else {
    echo strtoupper(substr($admin['first_name'],0,1));
}
?>
</div>


                <div class="profile-dropdown" id="profileDropdown">
    <h3>Admin Profile</h3>
    <form method="post" action="update_admin_profile_action.php" enctype="multipart/form-data">
        
        <!-- Profile Picture -->
        <div class="profile-picture-section">
            <div class="profile-avatar" id="profilePreview">
                <?php 
                if(!empty($admin['profile_photo'])) {
                    echo "<img src='uploads/admins{$admin['profile_photo']}' alt='Profile' style='width:100%; height:100%; border-radius:50%; object-fit:cover;'>";
                } else {
                    echo strtoupper(substr($admin['first_name'],0,1));
                }
                ?>
            </div>
            <label class="upload-btn">
                <i class="fas fa-upload"></i> Change Photo
                <input type="file" name="profile_photo" id="profilePhotoInput" accept="image/*" style="display:none;">
            </label>
        </div>

        <!-- Name & Email -->
        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name"
            value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email"
            value="<?php echo htmlspecialchars($admin['email']); ?>" required>

        <label for="password">New Password</label>
        <input type="password" id="password" name="password" placeholder="Leave blank to keep current">

        <button type="submit">Save Changes</button>
    </form>
    <hr>
    <a href="logout.php" class="logout-link">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

            </div>
            <!-- Dashboard Tab -->
            <div class="content-section active" id="dashboard">

                <div class="dashboard-header">
                    <h1>Dashboard Overview</h1>
                    <p>System performance & activity summary</p>
                </div>

                <!-- KPI CARDS -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <span>Total Users</span>
                            <i class="fas fa-users"></i>
                        </div>
                        <h2><?= $totalUsers ?></h2>

                        <?php if ($newUsersToday > 0): ?>
                        <small class="trend up">↑ <?= $newUsersToday ?> new today</small>
                        <?php else: ?>
                        <small class="trend neutral">No new users today</small>
                        <?php endif; ?>

                    </div>

                    <div class="kpi-card">
                        <div class="kpi-top">
                            <span>Vendors</span>
                            <i class="fas fa-building"></i>
                        </div>
                        <h2><?= $totalvendors ?></h2>
                        <?php if ($newvendorsToday > 0): ?>
                        <small class="trend up">↑ <?= $newvendorsToday ?> new today</small>
                        <?php else: ?>
                        <small class="trend neutral">No new vendors today</small>
                        <?php endif; ?>

                    </div>

                    <div class="kpi-card">
                        <div class="kpi-top">
                            <span>Bookings</span>
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h2><?= $totalBookings ?></h2>

                        <?php if ($bookingsToday > 0): ?>
                        <small class="trend up">↑ <?= $bookingsToday ?> today</small>
                        <?php else: ?>
                        <small class="trend neutral">No bookings today</small>
                        <?php endif; ?>

                    </div>

                    <div class="kpi-card highlight">
                        <div class="kpi-top">
                            <span>Revenue</span>
                            <i class="fas fa-wallet"></i>
                        </div>
                        <h2>Rs <?= number_format($revenueToday) ?></h2>

                        <?php if ($revenueToday > 0): ?>
                        <small class="trend up">↑ Earned today</small>
                        <?php else: ?>
                        <small class="trend neutral">No revenue today</small>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- LOWER GRID -->
                <div class="dashboard-lower">

                    <div class="panel">
                        <h3>Pending Actions</h3>
                        <ul class="action-list">
                            <li>🕒 Vendors to approve:
                                <b><?= $conn->query("SELECT COUNT(*) total FROM vendors WHERE is_approved=0")->fetch_assoc()['total'] ?></b>
                            </li>
                            <li>📅 Pending bookings:
                                <b><?= $conn->query("SELECT COUNT(*) total FROM bookings WHERE status='pending'")->fetch_assoc()['total'] ?></b>
                            </li>
                            <li>💳 Failed payments:
                                <b><?= $conn->query("SELECT COUNT(*) total FROM payments WHERE status='failed'")->fetch_assoc()['total'] ?></b>
                            </li>
                        </ul>
                    </div>

                    <div class="panel">
                        <h3>System Status</h3>
                        <ul class="status-list">
                            <li><span class="dot green"></span> API: Online</li>
                            <li><span class="dot green"></span> Database: Connected</li>
                            <li><span class="dot yellow"></span> Vendor Queue Active</li>
                        </ul>
                    </div>

                </div>

            </div>
            <!--manage user-->
<div class="content-section" id="manage-user">
    <h2>Manage Users</h2>
    <table class="data-table" border="1" cellpadding="10" id="usersTable">
        <thead>
            <tr>
                <th>User ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $result = $conn->query("SELECT * FROM users");
        while ($user = $result->fetch_assoc()):
        ?>
            <tr id="user-<?= $user['user_id'] ?>">
                <td><?= $user['user_id'] ?></td>
                <td><input type="text" value="<?= htmlspecialchars($user['first_name']) ?>" id="fname-<?= $user['user_id'] ?>"></td>
                <td><input type="text" value="<?= htmlspecialchars($user['last_name']) ?>" id="lname-<?= $user['user_id'] ?>"></td>
                <td><input type="email" value="<?= htmlspecialchars($user['email']) ?>" id="email-<?= $user['user_id'] ?>"></td>
                <td><input type="text" value="<?= htmlspecialchars($user['phone']) ?>" id="phone-<?= $user['user_id'] ?>"></td>
                <td><input type="text" value="<?= htmlspecialchars($user['address']) ?>" id="address-<?= $user['user_id'] ?>"></td>
                <td>
                    <button onclick="updateUserAjax(<?= $user['user_id'] ?>)">Save</button>
                    <button onclick="deleteUserAjax(<?= $user['user_id'] ?>)" style="color:red;">Delete</button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Manage Vendor Tab -->
<div class="content-section" id="manage-provider">
    <h2>Vendor Applications</h2>
    <table class="data-table" border="1" cellpadding="10" id="vendorsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Skills</th>
                <th>Location</th>
                <th>Phone</th>
                <th>Experience</th>
                <th>Action</th>
            </tr>
        </thead>
       <tbody>
<?php
$result = $conn->query("SELECT * FROM vendor_applications WHERE status='pending'");
while ($vendor = $result->fetch_assoc()):
?>
<tr id="vendor-<?= $vendor['vendor_id'] ?>">
    <td><?= $vendor['vendor_id'] ?></td>
    <td><?= htmlspecialchars($vendor['name']) ?></td>
    <td><?= htmlspecialchars($vendor['email']) ?></td>
    <td><?= htmlspecialchars($vendor['skills']) ?></td>
    <td><?= htmlspecialchars($vendor['location']) ?></td>
    <td><?= htmlspecialchars($vendor['phone']) ?></td>
    <td><?= htmlspecialchars($vendor['experience']) ?></td>
    <td>
        <button type="button"
        class="action-btn"
        data-id="<?= $vendor['vendor_id'] ?>"
        data-action="approve"
        style="color:green;">
    Approve
</button>

<button type="button"
        class="action-btn"
        data-id="<?= $vendor['vendor_id'] ?>"
        data-action="reject"
        style="color:red;">
    Reject
</button>

    </td>
</tr>
<?php endwhile; ?>
</tbody>

    </table>
</div>

            <!-- Manage Service Tab -->
            <div class="content-section" id="manage-service">
                <h2>Manage Services</h2>

                <form method="post" action="service_action.php">
                    <label>Category:</label>
                    <input type="text" name="category" required>

                    <label>Description:</label>
                    <textarea name="description" required></textarea>

                    <button type="submit" name="add_service">Add Service</button>
                </form>

                <table class="data-table" border="1" cellpadding="10">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
$services = $conn->query("SELECT * FROM services");
while ($s = $services->fetch_assoc()) {
    echo "<tr id='service_row_{$s['service_id']}'>
        <td>{$s['service_id']}</td>
        <td><span class='category'>{$s['category']}</span>
            <input type='text' class='edit-category' value='{$s['category']}' style='display:none;'>
        </td>
        <td><span class='description'>{$s['description']}</span>
            <input type='text' class='edit-description' value='{$s['description']}' style='display:none;'>
        </td>
        <td>
            <button class='edit-btn' onclick='enableEdit({$s['service_id']})'>Edit</button>
            <button class='save-btn' onclick='saveEdit({$s['service_id']})' style='display:none;'>Save</button>
            <button class='cancel-btn' onclick='cancelEdit({$s['service_id']})' style='display:none;'>Cancel</button>
            <a href='service_action.php?id={$s['service_id']}&action=delete' style='color:red;'>Delete</a>
        </td>
    </tr>";
}
?>
                    </tbody>

                </table>
            </div>

            <!-- Manage Bookings Tab -->
            <div class="content-section" id="bookings">
                <h2>Manage Bookings</h2>
                <table class="data-table" border="1" cellpadding="10">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>User Name</th>
                            <th>Service</th>
                            <th>Vendor</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
            $query = "
                SELECT b.booking_id, u.first_name, u.last_name, s.category, v.name AS vendor_name, b.booking_date, b.status
                FROM bookings b
                JOIN users u ON b.user_id = u.user_id
                JOIN services s ON b.service_id = s.service_id
                JOIN vendors v ON b.vendor_id = v.vendor_id
            ";
            $result = $conn->query($query);
            while ($b = $result->fetch_assoc()):
            ?>
                        <tr id="booking-<?= $b['booking_id']; ?>">
                            <td><?= $b['booking_id']; ?></td>
                            <td><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?></td>
                            <td><?= htmlspecialchars($b['category']); ?></td>
                            <td><?= htmlspecialchars($b['vendor_name']); ?></td>
                            <td><?= htmlspecialchars($b['booking_date']); ?></td>
                            <td id="status-<?= $b['booking_id']; ?>"><?= htmlspecialchars($b['status']); ?></td>
                            <td id="action-<?= $b['booking_id']; ?>">
                                <?php if ($b['status'] === 'pending'): ?>
                                <button onclick="updateBooking(<?= $b['booking_id']; ?>, 'accepted')"
                                    style="color:green;">Accept</button>
                                <button onclick="updateBooking(<?= $b['booking_id']; ?>, 'rejected')"
                                    style="color:red;">Reject</button>
                                <?php else: ?>
                                <span class="completed">Action Completed</span>
                                <?php endif; ?>
                            </td>

                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>


            <!-- Payments Tab -->
<div class="content-section" id="payments">
    <h2>Payments</h2>

    <div class="table-card">
        <div class="table-header">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="paymentSearch" placeholder="Search payments...">
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Booking ID</th>
                        <th>User</th>
                        <th>Vendor</th>
                        <th>Amount (Rs)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="paymentTableBody">
                    <?php
                    $payments = $conn->query("
                        SELECT p.payment_id, p.booking_id, p.amount, p.status, p.pay_date,
                               u.first_name AS user_fname, u.last_name AS user_lname,
                               v.name AS vendor_name
                        FROM payments p
                        JOIN bookings b ON p.booking_id = b.booking_id
                        JOIN users u ON b.user_id = u.user_id
                        JOIN vendors v ON b.vendor_id = v.vendor_id
                        ORDER BY p.pay_date DESC
                    ");
                    while($pay = $payments->fetch_assoc()):
                        // Set badge class based on status
                        $statusClass = '';
                        if($pay['status'] === 'success') $statusClass = 'badge active';
                        elseif($pay['status'] === 'failed') $statusClass = 'badge pending';
                        elseif($pay['status'] === 'pending') $statusClass = 'badge inactive';
                    ?>
                    <tr>
                        <td><?= $pay['payment_id'] ?></td>
                        <td><?= $pay['booking_id'] ?></td>
                        <td><?= htmlspecialchars($pay['user_fname'].' '.$pay['user_lname']) ?></td>
                        <td><?= htmlspecialchars($pay['vendor_name']) ?></td>
                        <td><?= number_format($pay['amount']) ?></td>
                        <td><span class="<?= $statusClass ?>"><?= ucfirst($pay['status']) ?></span></td>
                        <td><?= date('Y-m-d', strtotime($pay['pay_date'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

        </main>
    </div>
<script>//managing users
function updateUserAjax(userId) {
    const data = {
        action: 'update',
        user_id: userId,
        first_name: document.getElementById(`fname-${userId}`).value,
        last_name: document.getElementById(`lname-${userId}`).value,
        email: document.getElementById(`email-${userId}`).value,
        phone: document.getElementById(`phone-${userId}`).value,
        address: document.getElementById(`address-${userId}`).value
    };

    fetch('user_action_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => alert(res.message))
    .catch(err => alert('Server error: ' + err));
}

function deleteUserAjax(userId) {
    if(!confirm('Are you sure you want to delete this user?')) return;

    const payload = { action: 'delete', user_id: userId };
    console.log('Deleting user:', payload); // <--- debug
    fetch('user_action_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', user_id: userId })
    })
    .then(res => res.json())
    .then(res => {
        alert(res.message);
        if(res.success){
            const row = document.getElementById(`user-${userId}`);
            if(row) row.remove();
        }
    })
    .catch(err => alert('Server error: ' + err));
}</script>
    <script src="js/admin_dashboard.js"></script>
</body>

</html>