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
        $message = "<div class='alert alert-success'>Event created successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Create Event</h1>

  <?php echo $message; ?>

  <div class="card shadow mb-4">
    <div class="card-body">
      <form method="POST">
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
          <input type="date" name="event_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
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

<?php include '../includes/footer.php'; ?>
