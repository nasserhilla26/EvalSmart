<?php
// organizer/update_targets.php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';
header('Content-Type: application/json');
include '../includes/notification_service.php';

$organizer_id = (int)$_SESSION['user_id'];
$eq_id = isset($_POST['event_questionnaire_id']) ? intval($_POST['event_questionnaire_id']) : 0;
$targets_json = isset($_POST['targets']) ? $_POST['targets'] : '[]';
$targets = json_decode($targets_json, true);
if (!is_array($targets)) $targets = [];

if (!$eq_id) {
  echo json_encode(['success'=>false,'message'=>'Missing event_questionnaire_id']);
  exit;
}

// verify ownership (join to events)
$sql = "SELECT eq.id, e.event_title FROM event_questionnaire eq JOIN events e ON eq.event_id = e.event_id WHERE eq.id = ? AND e.organizer_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $eq_id, $organizer_id);
$stmt->execute();
$res = $stmt->get_result();
// ensure we got a result row
if ($res->num_rows === 0) {
  echo json_encode(['success'=>false,'message'=>'Unauthorized or not found']);
  exit;
}

$row = $res->fetch_assoc();
$event_title = isset($row['event_title']) ? $row['event_title'] : '';

// Start transaction (if using InnoDB). If MyISAM, this still works but no transaction.
$conn->begin_transaction();

// Delete existing targets for eq_id
$del = $conn->prepare("DELETE FROM event_questionnaire_targets WHERE event_questionnaire_id = ?");
$del->bind_param('i', $eq_id);
if (!$del->execute()) {
  $conn->rollback();
  echo json_encode(['success'=>false,'message'=>'Failed to delete existing targets: '.$conn->error]);
  exit;
}

// Insert new targets (skip duplicates - unique key will prevent)
$ins = $conn->prepare("INSERT INTO event_questionnaire_targets (event_questionnaire_id, department, program, position, created_at) VALUES (?, ?, ?, ?, NOW())");
$inserted = 0;
foreach ($targets as $t) {
  $dept = isset($t['department']) ? substr($t['department'], 0, 50) : 'ALL';
  $prog = isset($t['program']) ? substr($t['program'], 0, 50) : 'ALL';
  $pos  = isset($t['position']) ? substr($t['position'], 0, 50) : 'ALL';
  $ins->bind_param('isss', $eq_id, $dept, $prog, $pos);
  if ($ins->execute()) $inserted++;
}

$conn->commit();

// Notify evaluators about the updated targets (pass questionnaire id)
// notifyEvaluationAssigned($conn, $eq_id, $event_title, $organizer_id);

echo json_encode(['success'=>true, 'message'=>"Saved {$inserted} target(s)."]);
exit;
