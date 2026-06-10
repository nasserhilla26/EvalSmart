<?php
// admin/report_events.php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$dept = isset($_GET['department']) && $_GET['department'] !== '' ? $_GET['department'] : 'ALL';
$program = isset($_GET['program']) && $_GET['program'] !== '' ? $_GET['program'] : 'ALL';

try {
    if ($dept === 'ALL' && $program === 'ALL') {
        $sql = "SELECT DISTINCT e.event_id, e.event_title, e.event_date FROM event_questionnaire eq JOIN events e ON eq.event_id = e.event_id ORDER BY e.event_date DESC";
        $res = $conn->query($sql);
        $events = [];
        while ($r = $res->fetch_assoc()) $events[] = $r;
        echo json_encode(['success' => true, 'events' => $events], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($program === 'ALL') {
        $sql = "
            SELECT DISTINCT e.event_id, e.event_title, e.event_date
            FROM event_questionnaire eq
            JOIN events e ON eq.event_id = e.event_id
            JOIN event_questionnaire_targets t ON t.event_questionnaire_id = eq.id
            WHERE (t.department = 'ALL' OR t.department = ?)
            ORDER BY e.event_date DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $dept);
    } else {
        $sql = "
            SELECT DISTINCT e.event_id, e.event_title, e.event_date
            FROM event_questionnaire eq
            JOIN events e ON eq.event_id = e.event_id
            JOIN event_questionnaire_targets t ON t.event_questionnaire_id = eq.id
            WHERE (t.department = 'ALL' OR t.department = ?)
              AND (t.program = 'ALL' OR t.program = ?)
            ORDER BY e.event_date DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $dept, $program);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $events = [];
    while ($r = $res->fetch_assoc()) $events[] = $r;
    $stmt->close();

    echo json_encode(['success' => true, 'events' => $events], JSON_UNESCAPED_UNICODE);

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
}
