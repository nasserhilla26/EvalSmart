<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/db_connect.php';

$user_id = intval($_POST['user_id']);

mysqli_query($conn, "
  UPDATE users 
  SET status = IF(status='Active','Inactive','Active')
  WHERE user_id = $user_id
");

// Optional notification
mysqli_query($conn, "
  INSERT INTO notifications (user_id, title, message, type)
  VALUES ($user_id,
    'Account Status Updated',
    'Your account has been updated by admin.',
    'warning'
  )
");

echo json_encode(['success' => true]);