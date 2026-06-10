<?php
// admin/report_export.php
require '../vendor/autoload.php';
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/db_connect.php';


use Dompdf\Dompdf;


$format = $_GET['format'] ?? 'csv';
$date_from  = $_GET['date_from'] ?? null;
$date_to    = $_GET['date_to'] ?? null;
$department = $_GET['department'] ?? 'ALL';
$program    = $_GET['program'] ?? 'ALL';
$event_id   = isset($_GET['event_id']) && $_GET['event_id'] !== '' ? intval($_GET['event_id']) : null;

$where = [];
$params = [];
$types = '';

if (!empty($date_from)) { $where[] = "e.event_date >= ?"; $params[] = $date_from; $types .= 's'; }
if (!empty($date_to))   { $where[] = "e.event_date <= ?"; $params[] = $date_to; $types .= 's'; }
if (!empty($event_id))  { $where[] = "e.event_id = ?"; $params[] = $event_id; $types .= 'i'; }

if (!($department === 'ALL' && $program === 'ALL')) {
    $where[] = "
      EXISTS (
        SELECT 1 FROM event_questionnaire_targets t
        WHERE t.event_questionnaire_id = eq.id
          AND (t.department = 'ALL' OR t.department = ?)
          AND (t.program    = 'ALL' OR t.program = ?)
      )
    ";
    $params[] = $department; $params[] = $program; $types .= 'ss';
}

$respondent_filter_sql = '';
if ($department !== 'ALL') {
    if ($program !== 'ALL') {
        $respondent_filter_sql = " AND (SUBSTRING_INDEX(u.department, ' - ', 1) = ? OR SUBSTRING_INDEX(u.department, ' - ', 1) = 'ALL') AND (SUBSTRING_INDEX(u.department, ' - ', -1) = ? OR SUBSTRING_INDEX(u.department, ' - ', -1) = 'ALL')";
        $params[] = $department; $params[] = $program; $types .= 'ss';
    } else {
        $respondent_filter_sql = " AND (SUBSTRING_INDEX(u.department, ' - ', 1) = ? OR SUBSTRING_INDEX(u.department, ' - ', 1) = 'ALL')";
        $params[] = $department; $types .= 's';
    }
}

$sql = "
SELECT
  e.event_title,
  e.event_date,
  q.title AS questionnaire_title,
  COALESCE(ROUND(AVG(CASE WHEN ea.answer_text REGEXP '^[0-9]+(\\.[0-9]+)?$' THEN CAST(ea.answer_text AS DECIMAL(6,2)) END),2),0) AS avg_rating,
  COALESCE(COUNT(DISTINCT ea.user_id),0) AS total_respondents,
  COALESCE(GROUP_CONCAT(DISTINCT CASE WHEN ea.comments IS NOT NULL AND ea.comments <> '' THEN ea.comments END SEPARATOR '\\n'),'') AS comments,
  COALESCE(GROUP_CONCAT(DISTINCT CASE WHEN ea.suggestions IS NOT NULL AND ea.suggestions <> '' THEN ea.suggestions END SEPARATOR '\\n'),'') AS suggestions
FROM event_questionnaire eq
JOIN events e ON eq.event_id = e.event_id
JOIN questionnaire q ON eq.questionnaire_id = q.questionnaire_id
LEFT JOIN evaluation_answers ea ON ea.event_id = e.event_id
LEFT JOIN users u ON u.user_id = ea.user_id
WHERE 1=1
";

if (count($where)) {
    $sql .= ' AND ' . implode(' AND ', $where);
}

$sql .= $respondent_filter_sql;
$sql .= " GROUP BY eq.id ORDER BY e.event_date DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo "Prepare failed: " . $conn->error;
    exit;
}

if ($params) {
    $bind_names = [];
    $bind_names[] = $types;
    for ($i=0;$i<count($params);$i++){
        $bind_name = 'bind_'.$i;
        $$bind_name = $params[$i];
        $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="evalsmart_report_'.date('Ymd').'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['Event','Date','Questionnaire','Avg Rating','Respondents','Comments','Suggestions']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['event_title'],$r['event_date'],$r['questionnaire_title'],$r['avg_rating'],$r['total_respondents'],strip_tags($r['comments']),strip_tags($r['suggestions'])]);
    }
    fclose($out);
    exit;
}



$html = "<h2>EvalSmart Report</h2>";
$html .= "<p>Generated: ".date('F j, Y g:i A')."</p>";
$html .= "<table border='1' cellpadding='6' cellspacing='0' width='100%'><thead><tr><th>Event</th><th>Date</th><th>Questionnaire</th><th>Avg</th><th>Respondents</th></tr></thead><tbody>";
foreach ($rows as $r) {
    $html .= "<tr><td>{$r['event_title']}</td><td>{$r['event_date']}</td><td>{$r['questionnaire_title']}</td><td>{$r['avg_rating']}</td><td>{$r['total_respondents']}</td></tr>";
}
$html .= "</tbody></table>";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','landscape');
$dompdf->render();
$dompdf->stream('evalsmart_report_'.date('Ymd').'.pdf', ["Attachment"=>1]);
exit;
