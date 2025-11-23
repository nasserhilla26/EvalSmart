<?php
// admin/report_export.php
require '../vendor/autoload.php';
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/db_connect.php';

use Dompdf\Dompdf;


$format = $_GET['format'] ?? 'csv';

// Read filters
$date_from  = $_GET['date_from'] ?? null;
$date_to    = $_GET['date_to'] ?? null;
$department = $_GET['department'] ?? 'ALL';
$program    = $_GET['program'] ?? 'ALL';

/**
 * ============================================================
 * FUNCTION: fetch_rows()
 * Retrieves the full report data (no LIMIT), same as report_ajax.php
 * ============================================================
 */
function fetch_rows($conn, $date_from, $date_to, $department, $program) {

    $where = [];
    $params = [];

    // DATE FILTERS
    if (!empty($date_from)) {
        $where[] = "e.event_date >= ?";
        $params[] = $date_from;
    }
    if (!empty($date_to)) {
        $where[] = "e.event_date <= ?";
        $params[] = $date_to;
    }

    // DEPARTMENT FILTERING
    if ($department !== 'ALL') {
        $where[] = "
            (
                NOT EXISTS (
                    SELECT 1 FROM event_questionnaire_targets t 
                    WHERE t.event_questionnaire_id = eq.id
                )
                OR EXISTS (
                    SELECT 1 FROM event_questionnaire_targets t
                    WHERE 
                        t.event_questionnaire_id = eq.id
                        AND (t.department = 'ALL' OR t.department = ?)
                )
            )
        ";
        $params[] = $department;
    }

    // PROGRAM FILTERING
    if ($program !== 'ALL') {
        $where[] = "
            (
                NOT EXISTS (
                    SELECT 1 FROM event_questionnaire_targets t 
                    WHERE t.event_questionnaire_id = eq.id
                )
                OR EXISTS (
                    SELECT 1 FROM event_questionnaire_targets t
                    WHERE 
                        t.event_questionnaire_id = eq.id
                        AND (t.program = 'ALL' OR t.program = ?)
                )
            )
        ";
        $params[] = $program;
    }

    $where_sql = count($where) ? " AND " . implode(" AND ", $where) : "";

    // The SAME SQL as admin/report_ajax.php but WITHOUT LIMIT
    $sql = "
        SELECT
            e.event_id,
            e.event_title,
            e.event_date,
            q.questionnaire_id,
            q.title AS questionnaire_title,
            
            COUNT(DISTINCT ea.user_id) AS total_respondents,

            ROUND(AVG(CASE 
                WHEN ea.answer_text REGEXP '^[0-9]+$' 
                THEN CAST(ea.answer_text AS DECIMAL(5,2)) 
                ELSE NULL END), 2
            ) AS avg_rating,

            GROUP_CONCAT(
                DISTINCT 
                CASE WHEN ea.comments IS NOT NULL AND ea.comments <> '' 
                THEN ea.comments END
                SEPARATOR '\n'
            ) AS comments,

            GROUP_CONCAT(
                DISTINCT 
                CASE WHEN ea.suggestions IS NOT NULL AND ea.suggestions <> '' 
                THEN ea.suggestions END
                SEPARATOR '\n'
            ) AS suggestions
            
        FROM event_questionnaire eq
        JOIN events e ON eq.event_id = e.event_id
        JOIN questionnaire q ON eq.questionnaire_id = q.questionnaire_id
        LEFT JOIN evaluation_answers ea ON ea.event_id = e.event_id

        WHERE 1=1
        $where_sql

        GROUP BY eq.id
        ORDER BY e.event_date DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }

    return $rows;
}

// FETCH DATA NOW
$rows = fetch_rows($conn, $date_from, $date_to, $department, $program);



// ===========================
// CSV EXPORT
// ===========================
if ($format === 'csv') {

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="EvalSMART_Report_'.date('Ymd').'.csv"');

    $out = fopen('php://output', 'w');

    // Headers
    fputcsv($out, [
        'Event',
        'Date',
        'Questionnaire',
        'Avg Rating',
        'Respondents',
        'Comments',
        'Suggestions'
    ]);

    foreach ($rows as $r) {

        $eventDate = date('F j, Y', strtotime($r['event_date']));

        fputcsv($out, [
            $r['event_title'],
            $eventDate,
            $r['questionnaire_title'],
            $r['avg_rating'],
            $r['total_respondents'],
            strip_tags($r['comments']),
            strip_tags($r['suggestions'])
        ]);
    }

    fclose($out);
    exit;
}


// ===========================
// PDF EXPORT (Dompdf)
// ===========================
if ($format === 'pdf') {

    

    $html = "<h2>EvalSmart Report</h2>";
    $html .= "<table border='1' cellspacing='0' cellpadding='6' width='100%'>
                <thead>
                  <tr>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Questionnaire</th>
                    <th>Avg</th>
                    <th>Respondents</th>
                  </tr>
                </thead>
                <tbody>";

    foreach ($rows as $r) {

        $eventDate = date('F j, Y', strtotime($r['event_date']));

        $html .= "
            <tr>
                <td>{$r['event_title']}</td>
                <td>{$eventDate}</td>
                <td>{$r['questionnaire_title']}</td>
                <td>{$r['avg_rating']}</td>
                <td>{$r['total_respondents']}</td>
            </tr>
        ";
    }

    $html .= "</tbody></table>";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('EvalSMART_Report_'.date('Ymd').'.pdf', ["Attachment" => 1]);

    exit;
}
