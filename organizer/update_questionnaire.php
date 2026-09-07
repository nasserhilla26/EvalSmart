<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';
include '../includes/notification_service.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['questionnaire_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $organizer_id = $_SESSION['user_id'];

    $first_name = $_SESSION['first_name'];
    $last_name = $_SESSION['last_name'];
    $full_name = $first_name ." ". $last_name;

    $scale_id = intval($_POST['scale_id']);

    // Verify ownership
    $check = mysqli_query($conn, "SELECT * FROM questionnaire WHERE questionnaire_id='$id' AND created_by='$organizer_id'");
    if (mysqli_num_rows($check) === 0) {
        exit("Unauthorized or invalid questionnaire.");
    }

    // scale validation check, this section when you choose the default option this will alert, 
    // but the scale still override the old selected scale unless you choose new scale.
    if ($scale_id <= 0) {
        exit("Please select an evaluation scale.");
    }

    //lock the scale to prevents someone from bypassing the disabled dropdown using browser dev tools.
    $lockCheck = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM event_questionnaire
        WHERE questionnaire_id='$id'
    ");

    $lockData = mysqli_fetch_assoc($lockCheck);

    $isScaleLocked = ($lockData['total'] > 0);

    if ($isScaleLocked) {

        $currentScale = mysqli_query($conn, "
            SELECT scale_id
            FROM questionnaire
            WHERE questionnaire_id='$id'
        ");

        $currentScaleData = mysqli_fetch_assoc($currentScale);

        // Force original scale
        $scale_id = $currentScaleData['scale_id'];
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE QUESTIONNAIRE
    |--------------------------------------------------------------------------
    */

    $update = mysqli_query($conn, "
        UPDATE questionnaire 
        SET
            title='$title',
            description='$description',
            scale_id='$scale_id'
        WHERE questionnaire_id='$id'
    ");

    if (!$update) {
        exit("Failed to update questionnaire info: " . mysqli_error($conn));
    }

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


    notifyQuestionnaireModified($conn, 1, $full_name, $title, $organizer_id);

    echo "Questionnaire updated successfully with $saved question(s).";

    
}
?>
