<?php

include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1); // Admin

include '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
    exit;
}

$scale_id = intval($_POST['scale_id']);

$scale_name = mysqli_real_escape_string(
    $conn,
    trim($_POST['scale_name'])
);

$description = mysqli_real_escape_string(
    $conn,
    trim($_POST['description'])
);

// $scale_points = intval($_POST['scale_points']);

$scores = $_POST['scores'] ?? [];
$labels = $_POST['labels'] ?? [];

$min_values = $_POST['min_value'] ?? [];
$max_values = $_POST['max_value'] ?? [];
$interpretations = $_POST['interpretation'] ?? [];

if ($scale_name == '') {

    echo json_encode([
        'success' => false,
        'message' => 'Scale name is required.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK IF SCALE EXISTS
|--------------------------------------------------------------------------
*/
$checkScale = mysqli_query($conn, "
    SELECT *
    FROM evaluation_scales
    WHERE scale_id = '$scale_id'
");

if (mysqli_num_rows($checkScale) == 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Scale not found.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| LOCK CHECK
|--------------------------------------------------------------------------
| Prevent editing if already assigned
|--------------------------------------------------------------------------
*/
$used = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM questionnaire
    WHERE scale_id = '$scale_id'
");

$usedData = mysqli_fetch_assoc($used);

if ($usedData['total'] > 0) {

    echo json_encode([
        'success' => false,
        'message' => 'This scale is already assigned to a questionnaire and cannot be modified.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| DUPLICATE NAME CHECK
|--------------------------------------------------------------------------
*/
$duplicate = mysqli_query($conn, "
    SELECT scale_id
    FROM evaluation_scales
    WHERE scale_name = '$scale_name'
    AND scale_id != '$scale_id'
");

if (mysqli_num_rows($duplicate) > 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Scale name already exists.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| TRANSACTION
|--------------------------------------------------------------------------
*/
mysqli_begin_transaction($conn);

try {

    /*
    |--------------------------------------------------------------------------
    | UPDATE SCALE
    |--------------------------------------------------------------------------
    */
    mysqli_query($conn, "
        UPDATE evaluation_scales
        SET
            scale_name = '$scale_name',
            description = '$description'
        WHERE scale_id = '$scale_id'
    ");

    /*
    |--------------------------------------------------------------------------
    | DELETE OLD OPTIONS
    |--------------------------------------------------------------------------
    */
    mysqli_query($conn, "
        DELETE FROM evaluation_scale_options
        WHERE scale_id = '$scale_id'
    ");

    /*
    |--------------------------------------------------------------------------
    | INSERT UPDATED OPTIONS
    |--------------------------------------------------------------------------
    */
    foreach ($scores as $index => $score) {

        $score = intval($score);

        $label = mysqli_real_escape_string(
            $conn,
            trim($labels[$index])
        );

        mysqli_query($conn, "
            INSERT INTO evaluation_scale_options
            (
                scale_id,
                score,
                label,
                display_order
            )
            VALUES
            (
                '$scale_id',
                '$score',
                '$label',
                '$index'
            )
        ");
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE OLD INTERPRETATIONS
    |--------------------------------------------------------------------------
    */
    mysqli_query($conn, "
        DELETE FROM interpretation_ranges
        WHERE scale_id = '$scale_id'
    ");

    /*
    |--------------------------------------------------------------------------
    | INSERT UPDATED INTERPRETATIONS
    |--------------------------------------------------------------------------
    */
    foreach ($interpretations as $index => $interpretation) {

        $interpretation = mysqli_real_escape_string(
            $conn,
            trim($interpretation)
        );

        $min = floatval($min_values[$index]);
        $max = floatval($max_values[$index]);

        mysqli_query($conn, "
            INSERT INTO interpretation_ranges
            (
                scale_id,
                min_value,
                max_value,
                interpretation
            )
            VALUES
            (
                '$scale_id',
                '$min',
                '$max',
                '$interpretation'
            )
        ");
    }

    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Scale updated successfully.'
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}