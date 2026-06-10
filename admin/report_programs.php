<?php
// admin/report_programs.php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$dept = isset($_GET['department']) && $_GET['department'] !== '' ? $_GET['department'] : 'ALL';

try {
    $programs = ['ALL'];

    if ($dept === 'ALL') {
        // Distinct programs from targets
        $sql = "SELECT DISTINCT program FROM event_questionnaire_targets WHERE program IS NOT NULL AND program <> '' ORDER BY program";
        $res = $conn->query($sql);
        while ($r = $res->fetch_assoc()) {
            $p = $r['program'];
            if (!in_array($p, $programs)) $programs[] = $p;
        }
    } else {
        // Distinct programs where department matches or department = 'ALL'
        $sql = "SELECT DISTINCT program FROM event_questionnaire_targets WHERE (department = ? OR department = 'ALL') AND program IS NOT NULL AND program <> '' ORDER BY program";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $dept);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $p = $r['program'];
            if (!in_array($p, $programs)) $programs[] = $p;
        }
        $stmt->close();
    }

    echo json_encode(['success' => true, 'programs' => array_values($programs)], JSON_UNESCAPED_UNICODE);

} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
}
