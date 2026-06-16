<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $created_by = $_SESSION['user_id'];

    mysqli_begin_transaction($conn);

    try {

        /*
        |--------------------------------------------------------------------------
        | SAVE QUESTIONNAIRE
        |--------------------------------------------------------------------------
        */
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO questionnaire (title, description, created_by)
             VALUES (?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssi",
            $title,
            $description,
            $created_by
        );

        mysqli_stmt_execute($stmt);

        $questionnaire_id = mysqli_insert_id($conn);

        mysqli_stmt_close($stmt);

        /*
        |--------------------------------------------------------------------------
        | SAVE CATEGORIES
        |--------------------------------------------------------------------------
        */
        $categoryMap = [];

        $categories = $_POST['categories'] ?? [];


        $display_order = 1;

        if (!empty($categories)) {

            $stmtCategory = mysqli_prepare(
                $conn,
                "INSERT INTO questionnaire_categories
                (
                    questionnaire_id,
                    category_name,
                    display_order
                )
                VALUES (?, ?, ?)"
            );

            foreach ($categories as $category_name) {

                $category_name = trim($category_name);

                if ($category_name === '') {
                    continue;
                }

                mysqli_stmt_bind_param(
                    $stmtCategory,
                    "isi",
                    $questionnaire_id,
                    $category_name,
                    $display_order
                );

                mysqli_stmt_execute($stmtCategory);

                $categoryMap[$category_name] = mysqli_insert_id($conn);

                $display_order++;
            }

            mysqli_stmt_close($stmtCategory);
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE QUESTIONS
        |--------------------------------------------------------------------------
        */
        $questions = $_POST['questions'] ?? [];

        $saved = 0;

        $stmtQuestion = mysqli_prepare(
            $conn,
            "INSERT INTO questionnaire_questions
            (
                questionnaire_id,
                category_id,
                question_text,
                question_type,
                options
            )
            VALUES (?, ?, ?, ?, ?)"
        );

        foreach ($questions as $q) {

            $text = trim($q['text'] ?? '');
            $type = trim($q['type'] ?? '');

            if ($text === '' || $type === '') {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | CATEGORY LOOKUP
            |--------------------------------------------------------------------------
            */
            $category_id = null;

            if (!empty($q['category']) && isset($categoryMap[$q['category']])) {
                $category_id = $categoryMap[$q['category']];
            }

            /*
            |--------------------------------------------------------------------------
            | OPTIONS
            |--------------------------------------------------------------------------
            */
            $options = null;

            if (!empty($q['options'])) {

                $optionsArray = array_map(
                    'trim',
                    explode(',', $q['options'])
                );

                $options = json_encode($optionsArray);
            }

            mysqli_stmt_bind_param(
                $stmtQuestion,
                "iisss",
                $questionnaire_id,
                $category_id,
                $text,
                $type,
                $options
            );

            mysqli_stmt_execute($stmtQuestion);

            $saved++;
        }

        mysqli_stmt_close($stmtQuestion);

        /*
        |--------------------------------------------------------------------------
        | COMMIT
        |--------------------------------------------------------------------------
        */
        mysqli_commit($conn);

        echo "'{$title}' saved successfully with {$saved} question(s).";

    } catch (Exception $e) {

        mysqli_rollback($conn);

        echo "Failed to save questionnaire: " . $e->getMessage();
    }

}
?>
