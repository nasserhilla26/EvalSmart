<?php
// organizer/delete_target.php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';
header('Content-Type: application/json');

$tid = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
if (!$tid) {
  echo json_encode(['success'=>false,'message'=>'Missing target id']);
  exit;
}

// Verify the organizer owns this target via event_questionnaire -> events -> organizer_id
$sql = "SELECT t.id FROM event_questionnaire_targets t
        JOIN event_questionnaire eq ON t.event_questionnaire_id = eq.id
        JOIN events e ON eq.event_id = e.event_id
        WHERE t.id = ? AND e.organizer_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $tid, $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
  echo json_encode(['success'=>false,'message'=>'Unauthorized or not found']);
  exit;
}

$del = $conn->prepare("DELETE FROM event_questionnaire_targets WHERE id = ?");
$del->bind_param('i', $tid);
if ($del->execute()) {
  echo json_encode(['success'=>true,'message'=>'Target removed']);
} else {
  echo json_encode(['success'=>false,'message'=>'Failed to delete: '.$conn->error]);
}
exit;
