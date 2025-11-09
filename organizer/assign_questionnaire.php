<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // Organizer only

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

$organizer_id = $_SESSION['user_id'];
$questionnaire_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch all active events created by this organizer
$events = mysqli_query($conn, "SELECT event_id, event_title FROM events WHERE organizer_id='$organizer_id' ORDER BY event_date DESC");

// Fetch the selected questionnaire
$qdata = mysqli_query($conn, "SELECT * FROM questionnaire WHERE questionnaire_id='$questionnaire_id' AND created_by='$organizer_id'");
$questionnaire = mysqli_fetch_assoc($qdata);
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Assign Questionnaire to Event</h1>

  <div class="card shadow mb-4">
    <div class="card-body">
      <?php if ($questionnaire): ?>
        <div class="alert alert-info">
          <strong>Selected Questionnaire:</strong> <?php echo htmlspecialchars($questionnaire['title']); ?>
          <br>
          <small class="text-muted"><?php echo htmlspecialchars($questionnaire['description']); ?></small>
        </div>

        <form id="assignForm">
          <input type="hidden" name="questionnaire_id" value="<?php echo $questionnaire['questionnaire_id']; ?>">

          <div class="mb-3">
            <label class="form-label fw-bold">Select Event:</label>
            <select class="form-select" name="event_id" required>
              <option value="">-- Choose an Event --</option>
              <?php while ($row = mysqli_fetch_assoc($events)): ?>
                <option value="<?php echo $row['event_id']; ?>"><?php echo htmlspecialchars($row['event_title']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="text-end">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-link"></i> Assign Questionnaire
            </button>
          </div>
        </form>

      <?php else: ?>
        <div class="alert alert-danger">
          Invalid or unauthorized questionnaire selected.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
  // ✅ Handle form submission
  $('#assignForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
      url: 'save_assigned_questionnaire.php',
      type: 'POST',
      data: $(this).serialize(),
      success: function(response) {
        Swal.fire({
          icon: 'success',
          title: 'Questionnaire Assigned',
          text: response,
          confirmButtonColor: '#3085d6'
        }).then(() => window.location.href = 'manage_questionnaires.php');
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to assign questionnaire. Please try again.',
          confirmButtonColor: '#d33'
        });
      }
    });
  });
});
</script>
