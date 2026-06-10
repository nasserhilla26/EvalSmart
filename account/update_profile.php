<?php
include '../includes/db_connect.php';
header('Content-Type: application/json');

$user_id = $_POST['user_id'];

$first = mysqli_real_escape_string($conn, $_POST['first_name']);
$last = mysqli_real_escape_string($conn, $_POST['last_name']);
// $dept = mysqli_real_escape_string($conn, $_POST['department']);
// $pos = mysqli_real_escape_string($conn, $_POST['position']);

mysqli_query($conn, "
  UPDATE users 
  SET first_name='$first',
      last_name='$last'
  WHERE user_id=$user_id
");

echo json_encode(['success' => true]);