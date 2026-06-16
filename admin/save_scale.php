<?php

include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1); // Admin

include '../includes/db_connect.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);

    exit;
}

$scale_name = mysqli_real_escape_string(
    $conn,
    trim($_POST['scale_name'])
);

$description = mysqli_real_escape_string(
    $conn,
    trim($_POST['description'])
);

$scale_points = intval($_POST['scale_points']);

$scores = $_POST['scores'] ?? [];
$labels = $_POST['labels'] ?? [];

$min_values = $_POST['min_value'] ?? [];
$max_values = $_POST['max_value'] ?? [];
$interpretations = $_POST['interpretation'] ?? [];

if($scale_name == ''){

    echo json_encode([
        'success' => false,
        'message' => 'Scale name is required.'
    ]);

    exit;
}

$check = mysqli_query($conn, "
    SELECT scale_id
    FROM evaluation_scales
    WHERE scale_name = '$scale_name'
");

if(mysqli_num_rows($check) > 0){

    echo json_encode([
        'success' => false,
        'message' => 'Scale name already exists.'
    ]);

    exit;
}

mysqli_begin_transaction($conn);

try{

    /*
    |--------------------------------------------------------------------------
    | SAVE SCALE
    |--------------------------------------------------------------------------
    */

    mysqli_query($conn, "
        INSERT INTO evaluation_scales
        (
            scale_name,
            scale_points,
            description,
            status
        )
        VALUES
        (
            '$scale_name',
            '$scale_points',
            '$description',
            'Active'
        )
    ");

    $scale_id = mysqli_insert_id($conn);

    /*
    |--------------------------------------------------------------------------
    | SAVE OPTIONS
    |--------------------------------------------------------------------------
    */

    foreach($scores as $index => $score){

        $score = intval($score);

        $label = mysqli_real_escape_string(
            $conn,
            trim($labels[$index])
        );

        if($label == ''){
            continue;
        }

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
    | SAVE INTERPRETATIONS
    |--------------------------------------------------------------------------
    */

    foreach($interpretations as $index => $interpretation){

        $interpretation = mysqli_real_escape_string(
            $conn,
            trim($interpretation)
        );

        if($interpretation == ''){
            continue;
        }

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
        'message' => 'Scale created successfully.'
    ]);

}
catch(Exception $e){

    mysqli_rollback($conn);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

}