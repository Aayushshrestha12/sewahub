<?php
$host = "localhost";
$dbname = "sewahub";
$username = "root";
$password = "";

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: reusable query function
function runQuery($sql) {
    global $conn;
    $result = $conn->query($sql);
    if ($result === false) {
        die("Query Error: " . $conn->error);
    }
    return $result;
}
// Force MySQLi to throw errors instead of returning 'false'
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>


