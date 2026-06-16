<?php

include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);

include '../includes/db_connect.php';

$id = intval($_GET['id']);

$scaleQuery = mysqli_query($conn,"
    SELECT *
    FROM evaluation_scales
    WHERE scale_id='$id'
");

$scale = mysqli_fetch_assoc($scaleQuery);

if(!$scale){

    echo json_encode([
        'success' => false
    ]);

    exit;
}

$options = [];

$optionQuery = mysqli_query($conn,"
    SELECT *
    FROM evaluation_scale_options
    WHERE scale_id='$id'
    ORDER BY score DESC
");

while($row = mysqli_fetch_assoc($optionQuery)){
    $options[] = $row;
}

$ranges = [];

$rangeQuery = mysqli_query($conn,"
    SELECT *
    FROM interpretation_ranges
    WHERE scale_id='$id'
    ORDER BY max_value DESC
");

while($row = mysqli_fetch_assoc($rangeQuery)){
    $ranges[] = $row;
}

echo json_encode([
    'success' => true,
    'scale' => $scale,
    'options' => $options,
    'ranges' => $ranges
]);