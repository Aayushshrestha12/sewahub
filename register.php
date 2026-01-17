<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Common fields
    $userType        = $_POST['userType'] ?? "";
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $agreeToTerms    = isset($_POST['agreeToTerms']);

    $errors = [];

    // Basic validations
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($userType)) $errors[] = "Please select account type.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match.";
    if (!$agreeToTerms) $errors[] = "You must agree to Terms & Privacy Policy.";
    if (empty($phone)) $errors[] = "Phone number is required.";

    // User-specific fields
    if ($userType === 'user') {
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName  = trim($_POST['lastName'] ?? '');
        $address   = trim($_POST['address'] ?? '');

        if (empty($firstName)) $errors[] = "First Name is required.";
        if (empty($lastName)) $errors[] = "Last Name is required.";
        if (empty($address)) $errors[] = "Address is required.";
    }

    // Vendor-specific fields
    if ($userType === 'vendor') {
        $name       = trim($_POST['name'] ?? '');
        $skills     = trim($_POST['skills'] ?? '');
        $location   = trim($_POST['location'] ?? '');
        $experience = trim($_POST['experience'] ?? '');

        if (empty($name)) $errors[] = "Full Name / Business Name is required.";
        if (empty($skills)) $errors[] = "Skills are required.";
        if (empty($location)) $errors[] = "Location is required.";
        if (empty($experience)) $errors[] = "Experience is required.";
    }

    // Check if email already exists in correct table
    if ($userType === 'user') {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    } else {
        $stmt = $conn->prepare("SELECT vendor_id FROM vendors WHERE email=?");
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $errors[] = "Email already registered.";
    $stmt->close();

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($userType === 'user') {
            // Correct insert for users table
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, address, password)
    VALUES (?, ?, ?, ?, ?, ?)
");

// Bind exactly 6 values
$stmt->bind_param("ssssss", $firstName, $lastName, $email, $phone, $address, $hashedPassword);

        } else {
            $isApproved = 0; // default
            $stmt = $conn->prepare("INSERT INTO vendors (name, email, password, skills, location, phone, experience, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssis", $name, $email, $hashedPassword, $skills, $location, $phone, $experience, $isApproved);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "Account created successfully! Please log in.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Database Error: " . $stmt->error;
        }
        $stmt->close();
    }

    $_SESSION['errors'] = $errors;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sewa Hub - Registration</title>
<link rel="stylesheet" href="css/register.css">
</head>
<body>
<div class="container">
    <div class="form-wrapper">
        <div class="header">
            <h1 class="logo">Sewa Hub</h1>
            <p class="subtitle">Join our service marketplace</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Create Account</h2>
                <p class="card-description">Enter your details to get started</p>
            </div>

            <div class="card-content">
                <?php
                if (!empty($_SESSION['errors'])) {
                    echo '<div class="form-error">';
                    foreach ($_SESSION['errors'] as $error) {
                        echo "<p>$error</p>";
                    }
                    echo '</div>';
                    unset($_SESSION['errors']);
                }
                ?>

                <form id="registrationForm" method="POST" action="register.php">

  <!-- Account Type -->
  <div class="form-group">
    <label>Account Type</label>
    <label><input type="radio" name="userType" value="user" checked required> User</label>
    <label><input type="radio" name="userType" value="vendor" required> Vendor</label>
  </div>

  <!-- USER FIELDS -->
  <div id="userFields">
    <div class="form-group">
      <label>First Name</label>
      <input type="text" name="firstName">
    </div>

    <div class="form-group">
      <label>Last Name</label>
      <input type="text" name="lastName">
    </div>

    <div class="form-group">
      <label>Address</label>
      <input type="text" name="address">
    </div>
  </div>

  <!-- VENDOR FIELDS -->
  <div id="vendorFields" style="display:none;">
    <div class="form-group">
      <label>Full Name / Business Name</label>
      <input type="text" name="name">
    </div>

    <div class="form-group">
      <label>Skills</label>
      <input type="text" name="skills">
    </div>

    <div class="form-group">
      <label>Location</label>
      <input type="text" name="location">
    </div>

    <div class="form-group">
      <label>Experience</label>
      <input type="text" name="experience">
    </div>
  </div>

  <!-- CONTACT -->
  <div class="form-group">
    <label>Email</label>
    <input type="email" name="email" required>
  </div>

  <div class="form-group">
    <label>Phone</label>
    <input type="text" name="phone" required>
  </div>

  <!-- PASSWORD -->
  <div class="form-group">
    <label>Password</label>
    <input type="password" name="password" required>
  </div>

  <div class="form-group">
    <label>Confirm Password</label>
    <input type="password" name="confirmPassword" required>
  </div>

  <!-- TERMS -->
  <div class="form-group">
    <input type="checkbox" name="agreeToTerms" required>
    I agree to Terms & Privacy Policy
  </div>

  <button type="submit" class="btn">Create Account</button>
</form>

            </div>

            <div class="card-footer">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
</div>

<script>
const userTypeRadios = document.querySelectorAll('input[name="userType"]');
const userFields = document.getElementById('userFields');
const vendorFields = document.getElementById('vendorFields');

userTypeRadios.forEach(radio => {
    radio.addEventListener('change', () => {
        if (radio.value === 'vendor' && radio.checked) {
            vendorFields.style.display = 'block';
            userFields.style.display = 'none';
        } else {
            vendorFields.style.display = 'none';
            userFields.style.display = 'block';
        }
    });
});
</script>
</body>
</html>

