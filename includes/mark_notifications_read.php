<?php
include '../includes/auth.php';
include '../includes/db_connect.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) exit;

mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id' AND is_read = 0");
?>