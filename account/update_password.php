<?php
include '../includes/db_connect.php';
header('Content-Type: application/json');

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';

if (!$token || !$password) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Validate token
$res = mysqli_query($conn, "
  SELECT user_id, token_expiry 
  FROM users 
  WHERE reset_token='$token'
  LIMIT 1
");

if (mysqli_num_rows($res) === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$user = mysqli_fetch_assoc($res);

// Check expiry
if (strtotime($user['token_expiry']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Token expired']);
    exit;
}

$user_id = $user['user_id'];

// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Update password + clear token
mysqli_query($conn, "
  UPDATE users 
  SET password='$hashed', reset_token=NULL, token_expiry=NULL
  WHERE user_id=$user_id
");

echo json_encode(['success' => true]);