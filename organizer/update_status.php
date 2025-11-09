<?php
include '../includes/auth.php';
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = intval($_POST['event_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $organizer_id = $_SESSION['user_id'];

    $query = "
      UPDATE events 
      SET status='$status' 
      WHERE event_id='$event_id' AND organizer_id='$organizer_id'
    ";

    if (mysqli_query($conn, $query)) {
        echo "Event status updated successfully!";
    } else {
        echo "Error updating status: " . mysqli_error($conn);
    }
}
?>
