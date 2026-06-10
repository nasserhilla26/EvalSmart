<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/db_connect.php';

$id = intval($_POST['user_id']);
$type = $_POST['type'];

if ($type == 'approve') {
    mysqli_query($conn, "UPDATE users SET role_status='Approved' WHERE user_id=$id");
}
elseif ($type == 'reject') {
    mysqli_query($conn, "UPDATE users SET role_status='Rejected' WHERE user_id=$id");
}

echo json_encode(['success'=>true]);