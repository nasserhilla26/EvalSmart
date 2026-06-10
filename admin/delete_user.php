<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/db_connect.php';

$user_id = intval($_POST['user_id']);

mysqli_query($conn, "DELETE FROM users WHERE user_id = $user_id");

echo json_encode(['success' => true]);