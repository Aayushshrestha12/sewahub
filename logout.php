<?php
session_start();
session_unset();
session_destroy(); // clear all session data
header("Location: index.php");
exit();
?>
