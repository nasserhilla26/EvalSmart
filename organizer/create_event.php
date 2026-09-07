<?php
include '../includes/auth.php';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

// Handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['event_title']);
    $description = mysqli_real_escape_string($conn, $_POST['event_description']);
    $date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $venue = mysqli_real_escape_string($conn, $_POST['event_venue']);
    $organizer_id = $_SESSION['user_id'];

    $sql = "INSERT INTO events (organizer_id, event_title, event_description, event_date, event_venue)
            VALUES ('$organizer_id', '$title', '$description', '$date', '$venue')";

    if (mysqli_query($conn, $sql)) {

          echo "
          <script>
              document.addEventListener('DOMContentLoaded', function () {
                  Swal.fire({
                      icon: 'success',
                      title: 'Event Created!',
                      text: 'The event has been created successfully.',
                      confirmButtonText: 'OK',
                      allowOutsideClick: false
                  }).then(() => {
                      window.location.href = 'manage_events.php';
                  });
              });
          </script>";

      } else {

          echo "
          <script>
              document.addEventListener('DOMContentLoaded', function () {
                  Swal.fire({
                      icon: 'error',
                      title: 'Creation Failed',
                      text: " . json_encode(mysqli_error($conn)) . ",
                      confirmButtonText: 'OK'
                  });
              });
          </script>";

      }
}
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Create Event</h1>


  <div class="card shadow mb-4">
    <div class="card-body">
      <form method="POST" id="createEventForm">
        <div class="mb-3">
          <label>Event Title</label>
          <input type="text" name="event_title" class="form-control" required>
        </div>

        <div class="mb-3">
          <label>Description</label>
          <textarea name="event_description" class="form-control" rows="3" required></textarea>
        </div>

        <div class="mb-3">
          <label>Date</label>
          <input type="date" name="event_date" class="form-control" required>
        </div>

        <div class="mb-3">
          <label>Venue</label>
          <input type="text" name="event_venue" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Save Event</button>
      </form>
    </div>
  </div>
</div>


<script>
document.getElementById('createEventForm').addEventListener('submit', function(e) {

    e.preventDefault();

    Swal.fire({
        title: 'Create Event?',
        text: 'Are you sure you want to create this event?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Create',
        cancelButtonText: 'Cancel'
    }).then((result) => {

        if (result.isConfirmed) {
            this.submit();
        }

    });

});
</script>


<?php include '../includes/footer.php'; ?>
