<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id']);
    $questionnaire_id = intval($_POST['questionnaire_id']);
    $organizer_id = $_SESSION['user_id'];

    // Validate ownership — Organizer must own this event
    $check_event = mysqli_query($conn, "SELECT * FROM events WHERE event_id='$event_id' AND organizer_id='$organizer_id'");
    if (mysqli_num_rows($check_event) == 0) {
        http_response_code(403);
        exit("Unauthorized access. This event doesn't belong to you.");
    }

    // Check if this pair already exists
    $check_pair = mysqli_query($conn, "
        SELECT * FROM event_questionnaire 
        WHERE event_id='$event_id' 
        AND questionnaire_id='$questionnaire_id'
    ");

    if (mysqli_num_rows($check_pair) > 0) {
        echo "This questionnaire is already assigned to the selected event.";
        exit;
    }

    // Insert new record (do not replace or update)
    $insert = mysqli_query($conn, "
        INSERT INTO event_questionnaire (event_id, questionnaire_id, assigned_at)
        VALUES ('$event_id', '$questionnaire_id', NOW())
    ");

    if ($insert) {
        echo "Questionnaire successfully assigned to the event.";
    } else {
        $errno = mysqli_errno($conn);
        if ($errno === 1062) {
            echo "Already assigned to this event.";
        } else {
            echo "Database error: " . mysqli_error($conn);
        }
    }
}
?>
