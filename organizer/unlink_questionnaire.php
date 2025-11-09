<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id']);
    $questionnaire_id = intval($_POST['questionnaire_id']);
    $organizer_id = $_SESSION['user_id'];

    // Validate ownership: event must belong to this organizer
    $check_event = mysqli_query($conn, "SELECT * FROM events WHERE event_id='$event_id' AND organizer_id='$organizer_id'");
    if (mysqli_num_rows($check_event) == 0) {
        http_response_code(403);
        exit("Unauthorized action.");
    }

    // Delete the link
    $delete = mysqli_query($conn, "
        DELETE FROM event_questionnaire 
        WHERE event_id='$event_id' AND questionnaire_id='$questionnaire_id'
    ");

    if ($delete) {
        echo "Questionnaire successfully unlinked from the event.";
    } else {
        echo "Failed to unlink: " . mysqli_error($conn);
    }
}
?>
