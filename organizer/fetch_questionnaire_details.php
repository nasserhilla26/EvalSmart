<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $organizer_id = $_SESSION['user_id'];

    // Validate access
    $check = mysqli_query($conn, "SELECT * FROM questionnaire WHERE questionnaire_id='$id' AND created_by='$organizer_id'");
    if (mysqli_num_rows($check) === 0) {
        echo json_encode(["success" => false, "message" => "Unauthorized or questionnaire not found."]);
        exit;
    }

    $qdata = mysqli_fetch_assoc($check);

    // // Fetch questions
    // $questions = [];
    // $query = mysqli_query($conn, "SELECT * FROM questionnaire_questions WHERE questionnaire_id='$id'");
    // while ($row = mysqli_fetch_assoc($query)) {
    //     $questions[] = [
    //         "text" => $row['question_text'],
    //         "type" => $row['question_type'],
    //         "options" => $row['options'] ? json_decode($row['options'], true) : null
    //     ];
    // }

    $questions = [];

    $query = mysqli_query($conn, "
        SELECT
            qq.*,
            qc.category_name
        FROM questionnaire_questions qq
        LEFT JOIN questionnaire_categories qc
            ON qq.category_id = qc.category_id
        WHERE qq.questionnaire_id = '$id'
        ORDER BY
            qc.display_order ASC,
            qq.question_id ASC
    ");

    while ($row = mysqli_fetch_assoc($query)) {

        $category = $row['category_name'] ?: 'General';

        if (!isset($questions[$category])) {
            $questions[$category] = [];
        }

        $questions[$category][] = [
            "text" => $row['question_text'],
            "type" => $row['question_type'],
            "options" => $row['options']
                ? json_decode($row['options'], true)
                : null
        ];
    }

    echo json_encode([
        "success" => true,
        "title" => $qdata['title'],
        "description" => $qdata['description'],
        "questions" => $questions
    ]);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid request."]);
