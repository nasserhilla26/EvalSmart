<?php

include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);

include '../includes/db_connect.php';

header('Content-Type: application/json');

$id = intval($_POST['id']);

/*
|--------------------------------------------------------------------------
| CHECK IF SCALE EXISTS
|--------------------------------------------------------------------------
*/
$check = mysqli_query($conn,"
    SELECT *
    FROM evaluation_scales
    WHERE scale_id='$id'
");

if(mysqli_num_rows($check) == 0){

    echo json_encode([
        'success'=>false,
        'message'=>'Scale not found.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| LOCK CHECK
|--------------------------------------------------------------------------
*/
$used = mysqli_query($conn,"
    SELECT COUNT(*) AS total
    FROM questionnaire
    WHERE scale_id='$id'
");

$usedData = mysqli_fetch_assoc($used);

if($usedData['total'] > 0){

    echo json_encode([
        'success'=>false,
        'message'=>'This scale is already assigned to a questionnaire and cannot be deleted.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/
mysqli_begin_transaction($conn);

try{

    mysqli_query($conn,"
        DELETE FROM evaluation_scale_options
        WHERE scale_id='$id'
    ");

    mysqli_query($conn,"
        DELETE FROM interpretation_ranges
        WHERE scale_id='$id'
    ");

    mysqli_query($conn,"
        DELETE FROM evaluation_scales
        WHERE scale_id='$id'
    ");

    mysqli_commit($conn);

} catch(Exception $e){

    mysqli_rollback($conn);

}

/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/
echo json_encode([
    'success'=>true,
    'message'=>'Scale deleted successfully.'
]);