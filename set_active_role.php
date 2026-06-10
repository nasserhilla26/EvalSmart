<?php
session_start();
header('Content-Type: application/json');

// Validate POST
if (!isset($_POST['role_id']) || !isset($_SESSION['pending_roles'])) {
  echo json_encode(['success' => false, 'message' => 'Invalid request']);
  exit;
}

$selected_role = intval($_POST['role_id']);
$roles = $_SESSION['pending_roles'];

// Validate chosen role
if (!in_array($selected_role, $roles)) {
  echo json_encode(['success' => false, 'message' => 'Invalid role selected']);
  exit;
}

// Only update active_role, do NOT reset session
$_SESSION['active_role'] = $selected_role;

// Remove pending_roles only
unset($_SESSION['pending_roles']);

switch ($selected_role) {
  case 1: $redirect = 'admin/dashboard.php'; break;
  case 2: $redirect = 'organizer/dashboard.php'; break;
  case 3: $redirect = 'evaluator/dashboard.php'; break;
  default: $redirect = 'index.php';
}

echo json_encode(['success' => true, 'redirect' => $redirect]);
exit;
?>
