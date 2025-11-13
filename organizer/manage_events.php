<?php
include '../includes/auth.php';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

$organizer_id = $_SESSION['user_id'];

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM events WHERE event_id='$id' AND organizer_id='$organizer_id'");
}

// Fetch Organizer Events
$result = mysqli_query($conn, "SELECT * FROM events WHERE organizer_id='$organizer_id' ORDER BY created_at DESC");
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Manage My Events</h1>

  <div class="card shadow mb-4">
    <div class="card-body table-responsive">
      <table id="eventsTable" class="table table-stripped">
        <thead class="table-secondary">
          <tr>
            <th>#</th>
            <th>Event</th>
            <th>Date</th>
            <th>Venue</th>
            <th>Status</th>
            <th width="200">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($row['event_title']); ?></td>
              <td><?php echo date("F j, Y", strtotime($row['event_date'])); ?></td>
              <td><?php echo htmlspecialchars($row['event_venue']); ?></td>
              <td>
                <span class="badge bg-<?php echo ($row['status'] == 'Completed') ? 'success' : (($row['status'] == 'Cancelled') ? 'danger' : 'secondary'); ?>">
                  <?php echo $row['status']; ?>
                </span>
              </td>
              <td>

              <!-- Update Status -->
              <a href="#" 
                class="btn btn-sm btn-secondary statusBtn"
                data-id="<?php echo $row['event_id']; ?>"
                data-status="<?php echo htmlspecialchars($row['status']); ?>"
                title="Update Status">
                <i class="fas fa-sync-alt"></i>
              </a>


                <!-- VIEW BTN -->

                <a href="#" 
                  class="btn btn-sm btn-info viewBtn m-1"
                  data-id="<?php echo $row['event_id']; ?>"
                  data-title="<?php echo htmlspecialchars($row['event_title']); ?>"
                  data-description="<?php echo htmlspecialchars($row['event_description']); ?>"
                  data-date="<?php echo htmlspecialchars($row['event_date']); ?>"
                  data-venue="<?php echo htmlspecialchars($row['event_venue']); ?>"
                  data-status="<?php echo htmlspecialchars($row['status']); ?>"
                  title="View Event Details"
                >
                  <i class="fas fa-eye"></i>
                </a>

                <!-- Edit BTN -->
                <a href="#" 
                class="btn btn-sm btn-warning editBtn me-1"
                data-id="<?php echo $row['event_id']; ?>"
                data-title="<?php echo htmlspecialchars($row['event_title']); ?>"
                data-description="<?php echo htmlspecialchars($row['event_description']); ?>"
                data-date="<?php echo htmlspecialchars($row['event_date']); ?>"
                data-venue="<?php echo htmlspecialchars($row['event_venue']); ?>"
                title="Update Event Details"
                >
                <i class="fas fa-edit"></i>
                </a>
                <a href="manage_events.php?delete=<?php echo $row['event_id']; ?>" 
                   class="btn btn-sm btn-danger deleteBtn me-1" 
                   title="Delete Event"
                   >
                   <i class="fas fa-trash"></i>
                  </a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<!-- Modal for edit events -->

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning bg-opacity-25 text-white">
        <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="editEventForm" method="POST">
        <div class="modal-body">
          <input type="hidden" name="event_id" id="edit_event_id">

          <div class="mb-3">
            <label>Event Title</label>
            <input type="text" name="event_title" id="edit_event_title" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Description</label>
            <textarea name="event_description" id="edit_event_description" class="form-control" rows="3" required></textarea>
          </div>

          <div class="mb-3">
            <label>Date</label>
            <input type="date" name="event_date" id="edit_event_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
          </div>

          <div class="mb-3">
            <label>Venue</label>
            <input type="text" name="event_venue" id="edit_event_venue" class="form-control" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- View event modal -->

<!-- View Event Modal -->
<div class="modal fade" id="viewEventModal" tabindex="-1" aria-labelledby="viewEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="viewEventModalLabel">Event Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Event Title:</label>
            <p id="view_event_title" class="form-control-plaintext border-bottom"></p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Date:</label>
            <p id="view_event_date" class="form-control-plaintext border-bottom"></p>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Venue:</label>
            <p id="view_event_venue" class="form-control-plaintext border-bottom"></p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Status:</label>
            <p id="view_event_status" class="form-control-plaintext border-bottom"></p>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Description:</label>
          <p id="view_event_description" class="form-control-plaintext rounded p-2" style="min-height: 80px;"></p>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title" id="statusModalLabel">Update Event Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="statusForm" method="POST">
        <div class="modal-body">
          <input type="hidden" name="event_id" id="status_event_id">

          <div class="mb-3">
        <label for="event_status" class="form-label fw-bold">Select Status:</label>
        <select name="status" id="event_status" class="form-select" required>
          <option value="Completed">Completed</option>
          <option value="Cancelled">Cancelled</option>
        </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
        </div>
      </div>
    </div>



<?php include '../includes/footer.php'; ?>


<!-- Initialize DataTables -->
<script>
$(document).ready(function() {
  $('#eventsTable').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    order: [[2, 'desc']], // Default sort by Date column
    columnDefs: [
      { orderable: false, targets: [5] } // Disable sorting on Action column
    ]
  });
});
</script>


<!-- call the edit modal -->

<script>
$(document).ready(function() {

  // Show modal with selected event data
  $('.editBtn').on('click', function() {
    const id = $(this).data('id');
    const title = $(this).data('title');
    const desc = $(this).data('description');
    const date = $(this).data('date');
    const venue = $(this).data('venue');

    $('#edit_event_id').val(id);
    $('#edit_event_title').val(title);
    $('#edit_event_description').val(desc);
    $('#edit_event_date').val(date);
    $('#edit_event_venue').val(venue);

    $('#editEventModal').modal('show');
  });

// EDIT EVENT FORM (AJAX)
  $('#editEventForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
      url: 'update_event.php',
      type: 'POST',
      data: $(this).serialize(),
      success: function(response) {
        $('#editEventModal').modal('hide');
        Swal.fire({
          icon: 'success',
          title: 'Event Updated',
          text: 'Your changes were saved successfully!',
          confirmButtonColor: '#3085d6',
        }).then(() => location.reload());
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Unable to update event. Please try again.',
          confirmButtonColor: '#d33',
        });
      }
    });
  });



  // View Button Click
  $('.viewBtn').on('click', function() {
    const title = $(this).data('title');
    const desc = $(this).data('description');
    const date = $(this).data('date');
    const formattedDate = new Date(date).toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    });
    const venue = $(this).data('venue');
    const status = $(this).data('status');

    // Populate modal fields
    $('#view_event_title').text(title);
    $('#view_event_description').text(desc);
    $('#view_event_date').text(formattedDate);
    $('#view_event_venue').text(venue);
    $('#view_event_status').text(status);

    // Show modal
    $('#viewEventModal').modal('show');
  });

});


// 🟣 Open Status Modal
$('.statusBtn').on('click', function() {
  const id = $(this).data('id');
  const status = $(this).data('status');

  $('#status_event_id').val(id);
  $('#event_status').val(status);
  $('#statusModal').modal('show');
});

 // STATUS UPDATE FORM
  $('#statusForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
      url: 'update_status.php',
      type: 'POST',
      data: $(this).serialize(),
      success: function(response) {
        $('#statusModal').modal('hide');
        Swal.fire({
          icon: 'success',
          title: 'Status Updated',
          text: 'Event status has been successfully changed!',
          confirmButtonColor: '#3085d6',
        }).then(() => location.reload());
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Could not update event status.',
          confirmButtonColor: '#d33',
        });
      }
    });
  });


$('.deleteBtn').on('click', function(e) {
    e.preventDefault();
    const url = $(this).attr('href');

    Swal.fire({
      title: 'Are you sure?',
      text: 'This event will be permanently deleted!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it!',
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: url,
          type: 'GET',
          success: function() {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Event has been deleted successfully.',
              confirmButtonColor: '#3085d6',
            }).then(() => location.reload());
          },
          error: function() {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Failed to delete event.',
              confirmButtonColor: '#d33',
            });
          }
        });
      }
    });
  });



// Tooltip for View, Update, delete

document.addEventListener('DOMContentLoaded', function () {
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  })
});

</script>

