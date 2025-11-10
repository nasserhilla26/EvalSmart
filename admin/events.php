<?php
include '../includes/auth.php';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

$result = mysqli_query($conn, "
  SELECT e.*, u.last_name AS organizer_name 
  FROM events e
  JOIN users u ON e.organizer_id = u.user_id
  ORDER BY e.event_date DESC
");
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">All Events</h1>

  <div class="card shadow mb-4">
    <div class="card-body table-responsive">
      <table id="eventsTable" class="table table-bordered table-striped">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Event Title</th>
            <th>Date</th>
            <th>Venue</th>
            <th>Organizer</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($row['event_title']); ?></td>
              <td><?php echo htmlspecialchars($row['event_date']); ?></td>
              <td><?php echo htmlspecialchars($row['event_venue']); ?></td>
              <td><?php echo htmlspecialchars($row['organizer_name']); ?></td>
              <td><span class="badge bg-<?php echo ($row['status'] == 'Completed') ? 'success' : 'secondary'; ?>">
                <?php echo $row['status']; ?></span></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>


<script>
$(document).ready(function() {
  $('#eventsTable').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    order: [[2, 'desc']]
  });
});
</script>