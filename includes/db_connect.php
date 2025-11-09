<?php
// Database connection (eval_smart)
$host = "localhost";
$user = "root";     // your MySQL username
$pass = "";         // your MySQL password
$dbname = "eval_smart";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} 
?>
