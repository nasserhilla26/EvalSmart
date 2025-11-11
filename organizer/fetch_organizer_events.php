<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';

$organizer_id = $_SESSION['user_id'];
$result = mysqli_query($conn, "SELECT event_id, event_title FROM events WHERE organizer_id='$organizer_id' AND status='Ongoing' ORDER BY event_date DESC");

if (mysqli_num_rows($result) > 0) {
  echo '<option value="">-- Choose an Event --</option>';
  while ($row = mysqli_fetch_assoc($result)) {
    echo '<option value="' . $row['event_id'] . '">' . htmlspecialchars($row['event_title']) . '</option>';
  }
} else {
  echo '<option value="">No events available</option>';
}
?>
