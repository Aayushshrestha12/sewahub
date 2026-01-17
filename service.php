<?php
session_start();
include 'db.php';

// Get the service from URL
$service = $_GET['service'] ?? '';
if (!$service) {
    header('Location: index.php#services'); 
    exit();
}

// Fetch service info
$serviceStmt = $conn->prepare("SELECT image, description FROM services WHERE category = ?");
$serviceStmt->bind_param("s", $service);
$serviceStmt->execute();
$serviceResult = $serviceStmt->get_result();
$serviceData = $serviceResult->fetch_assoc();

$serviceImage = !empty($serviceData['image']) ? $serviceData['image'] : 'default.jpg';
$serviceDesc  = $serviceData['description'] ?? 'Trusted professionals at your doorstep';

// Check if user is logged in
$isUserLoggedIn = isset($_SESSION['user_id']) && ($_SESSION['loginType'] ?? '') === 'users';

// ==================== FETCH VENDORS WITH SERVICE DETAILS ====================
$stmt = $conn->prepare("
    SELECT v.vendor_id, v.name, v.experience, v.profile_photo,
           MIN(vs.price) AS price,
           MIN(vs.available_from) AS available_from,
           MAX(vs.available_to) AS available_to
    FROM vendors v
    JOIN vendor_services vs ON v.vendor_id = vs.vendor_id
    WHERE v.skills = ? AND v.is_approved = 1
    GROUP BY v.vendor_id
");
$stmt->bind_param("s", $service);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($service) ?> Services | SewaHub</title>
  <link rel="stylesheet" href="css/styles.css" />
</head>
<body>

<header class="navbar">
  <div class="logo"><span style="color:#1c034f;">Sewa</span><span style="color:#B8860B">Hub</span></div>
  <nav>
    <a href="index.php#services">All Services</a>
    <?php if ($isUserLoggedIn): ?>
      <a href="user_dashboard.php">Dashboard</a>
    <?php else: ?>
      <button class="open-login">Login</button>
    <?php endif; ?>
  </nav>
</header>

<!-- Hero Section -->
<section class="service-hero"
  style="
    background:
    linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
    url('/sewahub/uploads/services/<?= $serviceImage ?>')
    center/cover no-repeat;
  ">
  <div class="service-hero-content">
    <h1><?= htmlspecialchars($service) ?> Services</h1>
    <p><?= htmlspecialchars($serviceDesc) ?></p>
  </div>
</section>

<section class="service-trust-strip">
  <div>✔ Verified Vendors</div>
  <div>✔ 7-Day Service</div>
  <div>✔ Transparent Pricing</div>
</section>

<div class="service-wrapper">
  <section class="service-info">
    <div class="info-card">
      <h3>What’s Included</h3>
      <ul>
        <li>✔ Professional inspection</li>
        <li>✔ Proper tools & materials</li>
        <li>✔ Clean-up after service</li>
        <li>✔ Work warranty</li>
      </ul>
    </div>

    <div class="info-card">
      <h3>Why Choose Us</h3>
      <ul>
        <li>✔ Background-verified vendors</li>
        <li>✔ Real customer reviews</li>
        <li>✔ Service guarantee</li>
      </ul>
    </div>

    <div class="info-card">
      <h3>Pricing</h3>
      <p>Starting from <strong>Rs. 1500</strong></p>
      <p>Final cost depends on work scope</p>
    </div>
  </section>

  <!-- Vendor List -->
  <section class="vendor-list">
    <h2>Available <?= htmlspecialchars($service) ?> Near You</h2>
    <div class="vendor-grid">

    <?php if ($result->num_rows > 0): ?>
      <?php while ($vendor = $result->fetch_assoc()): ?>
        <div class="vendor-card">
          <div class="vendor-avatar">
            <?php if (!empty($vendor['profile_photo'])): ?>
              <img src="<?= htmlspecialchars($vendor['profile_photo']) ?>"
                   onerror="this.src='/sewahub/assets/avatar-default.png'">
            <?php else: ?>
              <?= strtoupper($vendor['name'][0]) ?>
            <?php endif; ?>
          </div>

          <h3><?= htmlspecialchars($vendor['name']) ?></h3>
          <p><?= $vendor['experience'] ?>+ years experience</p>
          <p class="vendor-rating">⭐ 4.8 (120 reviews)</p>

          <p class="vendor-price"> Rs. <?= number_format($vendor['price'], 0) ?></p>

          <p class="vendor-status">
            🟢 Available: <?= date('M d, H:i', strtotime($vendor['available_from'])) ?>
            - <?= date('M d, H:i', strtotime($vendor['available_to'])) ?>
          </p>

          <button class="book-btn" data-vendor="<?= $vendor['vendor_id'] ?>">
            Book Now
          </button>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="no-vendors">No vendors available for this service.</p>
    <?php endif; ?>

    </div>
  </section>
</div>

<footer class="footer">
  <p>© 2026 SewaHub | Trusted Local Services</p>
</footer>

<!-- AUTH MODAL -->
<div id="authModal" class="modal" style="display:none;">
  <div class="modal-box">
    <span class="close-btn">&times;</span>

    <!-- USER REGISTER -->
    <form id="userForm" class="auth-form" style="display:none;">
      <h2>User Registration</h2>
      <input type="text" name="first_name" placeholder="First Name" required>
      <input type="text" name="last_name" placeholder="Last Name" required>
      <input type="text" name="address" placeholder="Address" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="phone" name="phone" placeholder="Phone" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <button type="submit">Register</button>
    </form>

    <!-- USER LOGIN -->
    <form id="userLoginForm" class="auth-form" style="display:none;">
      <h2>Login</h2>
      <input type="hidden" name="role" value="user">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</div>

<script>
window.IS_USER_LOGGED_IN = <?= $isUserLoggedIn ? 'true' : 'false' ?>;

// Show/hide forms inside modal
function showForm(formId) {
  const forms = {
    userRegister: document.getElementById('userForm'),
    userLogin: document.getElementById('userLoginForm'),
  };
  Object.values(forms).forEach(f => f.style.display = 'none');
  if (forms[formId]) forms[formId].style.display = 'block';
}

// Close auth modal
document.querySelector('#authModal .close-btn').addEventListener('click', () => {
  document.getElementById('authModal').style.display = 'none';
});
window.addEventListener('click', (e) => {
  if (e.target.id === 'authModal') e.target.style.display = 'none';
});

// Handle Book Now clicks
document.querySelectorAll('.book-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const vendorId = btn.dataset.vendor;

    if (!window.IS_USER_LOGGED_IN) {
      document.getElementById('authModal').style.display = 'flex';
      showForm('userLogin');
    } else {
      window.location.href = 'user_dashboard.php';
    }
  });
});

// AJAX login
document.getElementById('userLoginForm').addEventListener('submit', function(e){
  e.preventDefault();
  const formData = new FormData(this);
  fetch('login.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        window.IS_LOGGED_IN = true;
        document.getElementById('authModal').style.display = 'none';
        window.location.href = 'user_dashboard.php';
      } else {
        alert(data.message || 'Login failed');
      }
    });
});


// AJAX booking form
document.getElementById('bookingForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('book_service.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if(data.success){
        alert('Booking successful!');
        document.getElementById('bookingModal').style.display = 'none';
      } else {
        alert(data.message || 'Booking failed');
      }
    });
});
</script>
</body>
</html>
