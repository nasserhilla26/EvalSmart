<?php
include '../includes/db_connect.php';
require '../mail/send_reset_email.php';
header('Content-Type: application/json');

$email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

// Check if user exists
$res = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' LIMIT 1");

if (mysqli_num_rows($res) === 0) {
    echo json_encode(['success' => false, 'message' => 'Email not found']);
    exit;
}

$user = mysqli_fetch_assoc($res);
$user_id = $user['user_id'];

// Generate token
$token = bin2hex(random_bytes(32));
$expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

// Save token
mysqli_query($conn, "
  UPDATE users 
  SET reset_token='$token', token_expiry='$expiry'
  WHERE user_id=$user_id
");

// TODO: Send email (STEP 2)
if (sendResetEmail($email, $token)) {
    echo json_encode([
        'success' => true,
        'message' => 'Reset link sent to your email'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send email. Try again.'
    ]);
}