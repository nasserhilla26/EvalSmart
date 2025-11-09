<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(3);
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id']);
    $questionnaire_id = intval($_POST['questionnaire_id']);
    $answers = $_POST['answers'] ?? [];
    $user_id = $_SESSION['user_id'];

    // Get static fields
    $comments = !empty($_POST['comments']) ? mysqli_real_escape_string($conn, $_POST['comments']) : null;
    $suggestions = !empty($_POST['suggestions']) ? mysqli_real_escape_string($conn, $_POST['suggestions']) : null;

    if (empty($answers)) {
        exit("No answers submitted.");
    }

    // Prevent duplicate submissions (optional safety)
    $check = mysqli_query($conn, "
        SELECT * FROM evaluation_answers 
        WHERE user_id='$user_id' AND event_id='$event_id' AND questionnaire_id='$questionnaire_id'
    ");
    if (mysqli_num_rows($check) > 0) {
        exit("You have already submitted an evaluation for this event.");
    }

    // Insert answers (1 question = 1 row)
    $first = true;
    foreach ($answers as $question_id => $answer_value) {
        $answer_value = mysqli_real_escape_string($conn, $answer_value);
        
        if ($first) {
            // Store comments & suggestions only once
            $sql = "
                INSERT INTO evaluation_answers 
                    (user_id, event_id, questionnaire_id, question_id, answer_text, comments, suggestions, date_answered)
                VALUES 
                    ('$user_id', '$event_id', '$questionnaire_id', '$question_id', '$answer_value', 
                    " . ($comments ? "'$comments'" : "NULL") . ", 
                    " . ($suggestions ? "'$suggestions'" : "NULL") . ", NOW())
            ";
            $first = false;
        } else {
            $sql = "
                INSERT INTO evaluation_answers 
                    (user_id, event_id, questionnaire_id, question_id, answer_text, date_answered)
                VALUES 
                    ('$user_id', '$event_id', '$questionnaire_id', '$question_id', '$answer_value', NOW())
            ";
        }

        mysqli_query($conn, $sql);
    }

    echo "Thank you! Your evaluation has been submitted.";
}
?>
