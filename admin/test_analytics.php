<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1); // only admin
include '../includes/db_connect.php';
include '../includes/analytics_helper.php';

$event_id = 11;
$scale_id = 5;
$average = 3;

echo "<pre>";

print_r(
    getCategoryAverage(
        $conn,
        $event_id
    )
);

echo "</pre>";


echo "<pre>";
print_r(
    getQuestionAverage(
        $conn,
        $event_id
    )
);
echo "</pre>";


echo "<pre>";
print_r(
    getInterpretation(
        $conn,
    $scale_id,
    $average
    )
);
echo "</pre>";