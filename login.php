<?php
session_start();
include 'db.php';

// Enable error reporting temporarily for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Set JSON header for AJAX
if ($isAjax) {
    header('Content-Type: application/json');
}

// Primary key mapping
$pkColumns = [
    'admins'  => 'admin_id',
    'vendors' => 'vendor_id',
    'users'   => 'user_id'
];

// Dashboard mapping
$accounts = [
    ['table' => 'admins',  'dashboard' => 'admin_dashboard.php'],
    ['table' => 'vendors', 'dashboard' => 'vendor_dashboard.php'],
    ['table' => 'users',   'dashboard' => 'user_dashboard.php'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $found = false;

    foreach ($accounts as $acc) {
    $table = $acc['table'];
    $pk = $pkColumns[$table];

    // Prepare query to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM {$table} WHERE email=? LIMIT 1");
    if (!$stmt) {
        die("Prepare failed for table {$table}: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Get result safely
    $result = $stmt->get_result();
    if (!$result) {
        die("Get result failed for table {$table}: " . $conn->error);
    }

    $user = $result->fetch_assoc();

    if ($user) {
        $found = true;
        $valid = false;

        // Check hashed password first
        if (password_verify($password, $user['password'])) {
            $valid = true;
        } 
        // If old plaintext password, upgrade to hash
        elseif ($password === $user['password']) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE {$table} SET password=? WHERE {$pk}=?");
            if (!$up) {
                die("Update prepare failed for table {$table}: " . $conn->error);
            }
            $up->bind_param("si", $newHash, $user[$pk]);
            $up->execute();
            $valid = true;
        }

        if ($valid) {
            // Set session variables
            $_SESSION['user_id'] = $user[$pk];
            $_SESSION['loginType'] = $table;
            $_SESSION['name'] = $user['first_name'] ?? $user['name'] ?? '';

            // Redirect or respond for AJAX
            if ($isAjax) {
                echo json_encode(['success' => true, 'role' => $table]);
            } else {
                header("Location: {$acc['dashboard']}");
            }
            exit;
        } else {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Incorrect password']);
            } else {
                $error = 'Incorrect password';
            }
            exit;
        }
    }
}


    if (!$found) {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Email not found']);
        } else {
            $error = 'Email not found';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Sewa Hub</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <div class="login-card">
        <h1>Sewa Hub</h1>
        <p>Enter your credentials to login</p>

        <?php if (!empty($error)) echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>'; ?>

        <form method="post" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</div>
</body>
</html>
