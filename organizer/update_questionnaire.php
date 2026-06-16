<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['questionnaire_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $organizer_id = $_SESSION['user_id'];

    // Verify ownership
    $check = mysqli_query($conn, "SELECT * FROM questionnaire WHERE questionnaire_id='$id' AND created_by='$organizer_id'");
    if (mysqli_num_rows($check) === 0) {
        exit("Unauthorized or invalid questionnaire.");
    }

    // Update questionnaire info
    $update = mysqli_query($conn, "
        UPDATE questionnaire 
        SET title='$title', description='$description'
        WHERE questionnaire_id='$id'
    ");

    if (!$update) {
        exit("Failed to update questionnaire info: " . mysqli_error($conn));
    }

    // // Delete old questions first
    // mysqli_query($conn, "DELETE FROM questionnaire_questions WHERE questionnaire_id='$id'");

    // // Insert updated question set
    // $questions = $_POST['questions'] ?? [];
    // $saved = 0;

    // foreach ($questions as $q) {
    //     $text = mysqli_real_escape_string($conn, $q['text']);
    //     $type = mysqli_real_escape_string($conn, $q['type']);
    //     $options = !empty($q['options']) ? json_encode(array_map('trim', explode(',', $q['options']))) : null;

    //     $sql = "INSERT INTO questionnaire_questions (questionnaire_id, question_text, question_type, options)
    //             VALUES ('$id', '$text', '$type', " . ($options ? "'$options'" : "NULL") . ")";
    //     if (mysqli_query($conn, $sql)) $saved++;
    // }

    /*
    |--------------------------------------------------------------------------
    | DELETE OLD DATA
    |--------------------------------------------------------------------------
    */
    mysqli_query($conn,
        "DELETE FROM questionnaire_questions
        WHERE questionnaire_id='$id'"
    );

    mysqli_query($conn,
        "DELETE FROM questionnaire_categories
        WHERE questionnaire_id='$id'"
    );

    /*
    |--------------------------------------------------------------------------
    | SAVE CATEGORIES
    |--------------------------------------------------------------------------
    */
    $categoryMap = [];

    $categories = $_POST['categories'] ?? [];

    $order = 1;

    foreach ($categories as $catName) {

        $catName = trim($catName);

        if ($catName == '') continue;

        $catName = mysqli_real_escape_string(
            $conn,
            $catName
        );

        $catSql = "
            INSERT INTO questionnaire_categories
            (
                questionnaire_id,
                category_name,
                display_order
            )
            VALUES
            (
                '$id',
                '$catName',
                '$order'
            )
        ";

        mysqli_query($conn, $catSql);

        $categoryMap[$catName] =
            mysqli_insert_id($conn);

        $order++;
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE QUESTIONS
    |--------------------------------------------------------------------------
    */
    $questions = $_POST['questions'] ?? [];

    $saved = 0;

    foreach ($questions as $q) {

        $text = mysqli_real_escape_string(
            $conn,
            trim($q['text'])
        );

        $type = mysqli_real_escape_string(
            $conn,
            trim($q['type'])
        );

        if ($text == '' || $type == '') {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORY LOOKUP
        |--------------------------------------------------------------------------
        */
        $category_id = "NULL";

        if (
            !empty($q['category']) &&
            isset($categoryMap[$q['category']])
        ) {
            $category_id =
                $categoryMap[$q['category']];
        }

        /*
        |--------------------------------------------------------------------------
        | OPTIONS
        |--------------------------------------------------------------------------
        */
        $options = null;

        if (!empty($q['options'])) {

            $options = json_encode(
                array_map(
                    'trim',
                    explode(',', $q['options'])
                )
            );

            $options = mysqli_real_escape_string(
                $conn,
                $options
            );
        }

        $sql = "
            INSERT INTO questionnaire_questions
            (
                questionnaire_id,
                category_id,
                question_text,
                question_type,
                options
            )
            VALUES
            (
                '$id',
                $category_id,
                '$text',
                '$type',
                " . ($options ? "'$options'" : "NULL") . "
            )
        ";

        if (mysqli_query($conn, $sql)) {
            $saved++;
        }
    }

    echo "Questionnaire updated successfully with $saved question(s).";
}
?>
