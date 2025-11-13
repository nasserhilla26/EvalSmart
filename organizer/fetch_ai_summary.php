<?php
include '../includes/db_connect.php';
$event_id = intval($_GET['event_id']);

$result = mysqli_query($conn, "
  SELECT summary_text, recommendations, generated_on 
  FROM ai_summary 
  WHERE event_id='$event_id'
");
$data = mysqli_fetch_assoc($result);

if ($data) {
  echo json_encode([
    'success' => true,
    'summary' => $data['summary_text'],
    'recommendations' => $data['recommendations'],
    'generated_on' => date('F j, Y g:i A', strtotime($data['generated_on']))
  ]);
} else {
  echo json_encode(['success' => false]);
}
?>
