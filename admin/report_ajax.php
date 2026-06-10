<?php
// admin/report_ajax.php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/db_connect.php';
header('Content-Type: application/json; charset=utf-8');

set_error_handler(function($errno, $errstr, $errfile, $errline){
    http_response_code(500);
    echo json_encode(['error' => "$errstr on line $errline"]);
    exit;
});

try {
    $date_from  = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? $_GET['date_from'] : null;
    $date_to    = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] : null;
    $department = isset($_GET['department']) && $_GET['department'] !== '' ? $_GET['department'] : 'ALL';
    $program    = isset($_GET['program']) && $_GET['program'] !== '' ? $_GET['program'] : 'ALL';
    $event_id   = isset($_GET['event_id']) && $_GET['event_id'] !== '' ? intval($_GET['event_id']) : null;

    $where = [];
    $params = [];
    $types = '';

    if ($date_from) { $where[] = "e.event_date >= ?"; $params[] = $date_from; $types .= 's'; }
    if ($date_to)   { $where[] = "e.event_date <= ?"; $params[] = $date_to; $types .= 's'; }
    if ($event_id)  { $where[] = "e.event_id = ?"; $params[] = $event_id; $types .= 'i'; }

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

    // Base query - use SUBSTRING_INDEX to split users.department into dept/program
    $sql = "
        SELECT
            e.event_id,
            e.event_title,
            e.event_date,
            q.title AS questionnaire_title,

            COALESCE(
              ROUND(AVG(
                CASE WHEN ea.answer_text REGEXP '^[0-9]+(\\.[0-9]+)?$'
                     THEN CAST(ea.answer_text AS DECIMAL(6,2))
                     ELSE NULL END
              ), 2),
              0
            ) AS avg_rating,

            COALESCE(COUNT(DISTINCT ea.user_id), 0) AS total_respondents,

            COALESCE(
              GROUP_CONCAT(DISTINCT CASE WHEN ea.comments IS NOT NULL AND ea.comments <> '' THEN ea.comments END SEPARATOR '\\n'),
              ''
            ) AS comments,

            COALESCE(
              GROUP_CONCAT(DISTINCT CASE WHEN ea.suggestions IS NOT NULL AND ea.suggestions <> '' THEN ea.suggestions END SEPARATOR '\\n'),
              ''
            ) AS suggestions

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

    // Respondent filtering: use parsed department/program from users.department
    if ($department !== 'ALL') {
        if ($program !== 'ALL') {
            // Compare parsed dept and prog to targets
            $sql .= " AND (SUBSTRING_INDEX(u.department, ' - ', 1) = ? OR SUBSTRING_INDEX(u.department, ' - ', 1) = 'ALL')";
            $params[] = $department; $types .= 's';
            $sql .= " AND (SUBSTRING_INDEX(u.department, ' - ', -1) = ? OR SUBSTRING_INDEX(u.department, ' - ', -1) = 'ALL')";
            $params[] = $program; $types .= 's';
        } else {
            $sql .= " AND (SUBSTRING_INDEX(u.department, ' - ', 1) = ? OR SUBSTRING_INDEX(u.department, ' - ', 1) = 'ALL')";
            $params[] = $department; $types .= 's';
        }
    }

    $sql .= " GROUP BY eq.id ORDER BY e.event_date DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) throw new Exception('Prepare failed: ' . $conn->error);

    if ($params) {
        // bind params (call_user_func_array trick)
        $bind_names = [];
        $bind_names[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind_name = 'bind_'.$i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
    $res = $stmt->get_result();
    if ($res === false) throw new Exception('get_result failed: ' . $stmt->error);

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = [
            'event_title' => $row['event_title'] ?? '',
            'event_date' => $row['event_date'] ?? '',
            'questionnaire_title' => $row['questionnaire_title'] ?? '',
            'avg_rating' => isset($row['avg_rating']) ? (string)$row['avg_rating'] : '0',
            'total_respondents' => isset($row['total_respondents']) ? (int)$row['total_respondents'] : 0,
            'comments' => $row['comments'] ?? '',
            'suggestions' => $row['suggestions'] ?? ''
        ];
    }

    echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['error' => $ex->getMessage()]);
    exit;
}
