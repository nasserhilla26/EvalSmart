<?php
include '../includes/auth.php';
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = intval($_POST['event_id']);
    $title = mysqli_real_escape_string($conn, $_POST['event_title']);
    $description = mysqli_real_escape_string($conn, $_POST['event_description']);
    $date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $venue = mysqli_real_escape_string($conn, $_POST['event_venue']);

    $organizer_id = $_SESSION['user_id'];

    $query = "
      UPDATE events 
      SET event_title='$title',
          event_description='$description',
          event_date='$date',
          event_venue='$venue'
      WHERE event_id='$event_id' AND organizer_id='$organizer_id'
    ";

    if (mysqli_query($conn, $query)) {
        echo "Event updated successfully!";
    } else {
        echo " Error updating event: " . mysqli_error($conn);
    }
}
?>
