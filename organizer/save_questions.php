<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // Organizer only
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    $organizer_id = $_SESSION['user_id'];

    // Ensure this event belongs to the organizer
    $check = mysqli_query($conn, "SELECT * FROM events WHERE event_id='$event_id' AND organizer_id='$organizer_id'");
    if (mysqli_num_rows($check) == 0) {
        exit("Unauthorized access.");
    }

    $questions = $_POST['questions'] ?? [];
    $saved = 0;

    foreach ($questions as $q) {
        $text = mysqli_real_escape_string($conn, $q['text']);
        $type = mysqli_real_escape_string($conn, $q['type']);
        $options = !empty($q['options']) ? json_encode(array_map('trim', explode(',', $q['options']))) : null;

        $sql = "INSERT INTO event_questionnaire (event_id, question_text, question_type, options)
                VALUES ('$event_id', '$text', '$type', " . ($options ? "'$options'" : "NULL") . ")";
        if (mysqli_query($conn, $sql)) $saved++;
    }

    echo "$saved questions saved for event ID $event_id.";
}
?>
