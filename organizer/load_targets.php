<?php
// organizer/load_targets.php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';

header('Content-Type: application/json');

$eq_id = isset($_GET['eq_id']) ? intval($_GET['eq_id']) : 0;
if (!$eq_id) {
  echo json_encode(['success'=>false, 'message'=>'Missing event_questionnaire id']);
  exit;
}

// Verify organizer owns the event_questionnaire (join to events -> organizer_id)
$sql = "SELECT eq.id FROM event_questionnaire eq
        JOIN events e ON eq.event_id = e.event_id
        WHERE eq.id = ? AND e.organizer_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $eq_id, $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
  echo json_encode(['success'=>false, 'message'=>'Unauthorized or not found']);
  exit;
}

$q = $conn->prepare("SELECT id, department, program, position, created_at FROM event_questionnaire_targets WHERE event_questionnaire_id = ? ORDER BY id ASC");
$q->bind_param('i', $eq_id);
$q->execute();
$r = $q->get_result();
$rows = [];
while ($row = $r->fetch_assoc()) {
  $rows[] = $row;
}

echo json_encode(['success'=>true, 'targets'=>$rows]);
exit;
?>
