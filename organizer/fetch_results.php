<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['event_id'])) {
  echo json_encode(["success" => false, "message" => "No event specified."]);
  exit;
}

$event_id = intval($_GET['event_id']);

// Fetch all questions
$q_query = mysqli_query($conn, "
  SELECT DISTINCT 
    qq.question_id, 
    qq.question_text, 
    qq.question_type
  FROM questionnaire_questions qq
  JOIN event_questionnaire eq 
    ON qq.questionnaire_id = eq.questionnaire_id
  JOIN evaluation_answers ea 
    ON qq.question_id = ea.question_id
  WHERE eq.event_id = '$event_id'
  AND ea.event_id = '$event_id'
");




$summary = [];
while ($q = mysqli_fetch_assoc($q_query)) {
  $qid = $q['question_id'];
  $text = $q['question_text'];
  $type = $q['question_type'];

  if ($type == 'rating') {
    $res = mysqli_query($conn, "SELECT AVG(CAST(answer_text AS DECIMAL(5,2))) AS avg_score FROM evaluation_answers WHERE event_id='$event_id' AND question_id='$qid'");
    $avg = mysqli_fetch_assoc($res)['avg_score'] ?? 0;
    $summary[] = ["question" => $text, "value" => number_format($avg, 2)];
  } else {
    $res = mysqli_query($conn, "
      SELECT answer_text, COUNT(*) as cnt 
      FROM evaluation_answers 
      WHERE event_id='$event_id' AND question_id='$qid' 
      GROUP BY answer_text 
      ORDER BY cnt DESC LIMIT 1
    ");
    $row = mysqli_fetch_assoc($res);
    $summary[] = ["question" => $text, "value" => $row['answer_text'] ?? '—'];
  }
}

// Comments and suggestions
$comments = [];
$c_query = mysqli_query($conn, "
  SELECT DISTINCT comments, suggestions
  FROM evaluation_answers 
  WHERE event_id='$event_id' 
  AND (comments IS NOT NULL OR suggestions IS NOT NULL)
");
while ($c = mysqli_fetch_assoc($c_query)) {
  $comments[] = [
    "comment" => $c['comments'],
    "suggestion" => $c['suggestions']
  ];
}


// $groupedSummary = [];

// while ($q = mysqli_fetch_assoc($q_query)) {
//   $qid = $q['question_id'];
//   $qTitle = $q['questionnaire_title'];
//   $text = $q['question_text'];
//   $type = $q['question_type'];

//   if (!isset($groupedSummary[$qTitle])) {
//     $groupedSummary[$qTitle] = [];
//   }

//   if ($type == 'rating') {
//     $res = mysqli_query($conn, "
//       SELECT AVG(CAST(answer_text AS DECIMAL(5,2))) AS avg_score 
//       FROM evaluation_answers 
//       WHERE event_id='$event_id' AND question_id='$qid'
//     ");
//     $avg = mysqli_fetch_assoc($res)['avg_score'] ?? 0;

//     $groupedSummary[$qTitle][] = [
//       "question" => $text,
//       "value" => number_format($avg, 2)
//     ];

//   } else {
//     $res = mysqli_query($conn, "
//       SELECT answer_text, COUNT(*) as cnt 
//       FROM evaluation_answers 
//       WHERE event_id='$event_id' AND question_id='$qid'
//       GROUP BY answer_text 
//       ORDER BY cnt DESC LIMIT 1
//     ");
//     $row = mysqli_fetch_assoc($res);

//     $groupedSummary[$qTitle][] = [
//       "question" => $text,
//       "value" => $row['answer_text'] ?? '—'
//     ];
//   }
// }


echo json_encode([
  "success" => true,
  "summary" => $summary,
  "comments" => $comments
]);
?>
