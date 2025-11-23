<?php
// admin/report_ajax.php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/db_connect.php';
header('Content-Type: application/json');

// read filters
$date_from = !empty($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to   = !empty($_GET['date_to']) ? $_GET['date_to'] : null;
$department = $_GET['department'] ?? 'ALL';
$program = $_GET['program'] ?? 'ALL';

// Build SQL with safe escaping
$params = [];
$where = [];

// filter by date range
if ($date_from) { $where[] = "e.event_date >= ?"; $params[] = $date_from; }
if ($date_to)   { $where[] = "e.event_date <= ?"; $params[] = $date_to; }

// filter by department/program — use targets or users? we'll filter events that have targets matching or ALL
if ($department !== 'ALL') {
  $where[] = "(NOT EXISTS (SELECT 1 FROM event_questionnaire_targets t WHERE t.event_questionnaire_id = eq.id) OR EXISTS (SELECT 1 FROM event_questionnaire_targets t WHERE t.event_questionnaire_id = eq.id AND (t.department='ALL' OR t.department=?)))";
  $params[] = $department;
}
if ($program !== 'ALL') {
  $where[] = " (NOT EXISTS (SELECT 1 FROM event_questionnaire_targets t WHERE t.event_questionnaire_id = eq.id) OR EXISTS (SELECT 1 FROM event_questionnaire_targets t WHERE t.event_questionnaire_id = eq.id AND (t.program='ALL' OR t.program=?)))";
  $params[] = $program;
}

$where_sql = count($where) ? ' AND ' . implode(' AND ', $where) : '';

// Aggregation: avg rating from evaluation_answers.answer_text where rating-type questions stored as numbers or parse as needed.
// For simplicity assume rating answers are stored numeric in evaluation_answers.answer_text for rating questions.
$sql = "
SELECT
  e.event_id,
  e.event_title,
  e.event_date,
  q.questionnaire_id,
  q.title AS questionnaire_title,
  COUNT(DISTINCT ea.user_id) AS total_respondents,
  ROUND(AVG(CAST(ea.answer_text AS DECIMAL(5,2))),2) AS avg_rating
FROM event_questionnaire eq
JOIN events e ON eq.event_id = e.event_id
JOIN questionnaire q ON eq.questionnaire_id = q.questionnaire_id
LEFT JOIN evaluation_answers ea ON ea.event_id = e.event_id
WHERE 1=1
{$where_sql}
GROUP BY eq.id
ORDER BY e.event_date DESC
LIMIT 1000
";

$stmt = $conn->prepare($sql);
if ($params) {
  // build types string
  $types = str_repeat('s', count($params));
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($r = $res->fetch_assoc()) $data[] = $r;

echo json_encode(['data' => $data]);



// $sql = "
// SELECT
//   e.event_id,
//   e.event_title,
//   e.event_date,
//   q.questionnaire_id,
//   q.title AS questionnaire_title,
//   COUNT(DISTINCT ea.user_id) AS total_respondents,
//   ROUND(AVG(CAST(ea.answer_text AS DECIMAL(5,2))),2) AS avg_rating,
//   GROUP_CONCAT(DISTINCT CASE WHEN ea.comments IS NOT NULL AND ea.comments <> '' THEN CONCAT('<p>', ea.comments, '</p>') END SEPARATOR '') AS comments,
//   GROUP_CONCAT(DISTINCT CASE WHEN ea.suggestions IS NOT NULL AND ea.suggestions <> '' THEN CONCAT('<p>', ea.suggestions, '</p>') END SEPARATOR '') AS suggestions
// FROM event_questionnaire eq
// JOIN events e ON eq.event_id = e.event_id
// JOIN questionnaire q ON eq.questionnaire_id = q.questionnaire_id
// LEFT JOIN evaluation_answers ea ON ea.event_id = e.event_id
// WHERE 1=1
// {$where_sql}
// GROUP BY eq.id
// ORDER BY e.event_date DESC
// LIMIT 1000
// ";