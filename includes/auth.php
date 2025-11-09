<?php
session_start();
// If user is NOT logged in, redirect to login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

?>


