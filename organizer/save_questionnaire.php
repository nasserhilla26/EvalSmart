<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $created_by = $_SESSION['user_id'];

    // Insert questionnaire
    $sql = "INSERT INTO questionnaire (title, description, created_by) VALUES ('$title', '$description', '$created_by')";
    if (mysqli_query($conn, $sql)) {
        $questionnaire_id = mysqli_insert_id($conn);

        // Insert each question
        $questions = $_POST['questions'] ?? [];
        $saved = 0;
        foreach ($questions as $q) {
            $text = mysqli_real_escape_string($conn, $q['text']);
            $type = mysqli_real_escape_string($conn, $q['type']);
            $options = !empty($q['options']) ? json_encode(array_map('trim', explode(',', $q['options']))) : null;

            $q_sql = "INSERT INTO questionnaire_questions (questionnaire_id, question_text, question_type, options)
                      VALUES ('$questionnaire_id', '$text', '$type', " . ($options ? "'$options'" : "NULL") . ")";
            if (mysqli_query($conn, $q_sql)) $saved++;
        }

        echo "'$title' saved successfully with $saved question(s).";
    } else {
        echo "Failed to save questionnaire: " . mysqli_error($conn);
    }
}
?>
