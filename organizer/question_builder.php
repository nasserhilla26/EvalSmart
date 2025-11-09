<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // Organizer only

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

// Fetch organizer's active events
$organizer_id = $_SESSION['user_id'];
$events = mysqli_query($conn, "SELECT event_id, event_title FROM events WHERE organizer_id='$organizer_id'");
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Evaluation Question Builder</h1>

  <div class="card shadow mb-4 w-75">
    <div class="card-body">
      <form id="questionForm">
        <div class="mb-3">
          <label class="form-label fw-bold">Select Event:</label>
          <select class="form-select" name="event_id" id="event_id" required>
            <option value="">-- Choose an Event --</option>
            <?php while ($row = mysqli_fetch_assoc($events)): ?>
              <option value="<?php echo $row['event_id']; ?>"><?php echo htmlspecialchars($row['event_title']); ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <hr>

        <!-- <h5 class="mb-3">Add Questions</h5> -->

        <div id="questionContainer"></div>

        
        <div class="row mt-2">
            <did class="col">
            <button type="button" class="btn btn-success" id="addQuestionBtn">
                <i class="fas fa-plus"></i> Add Question
            </button>
            </did>
            <did class="col text-end">
                <button type="submit" class="btn btn-primary">Save Questionnaire</button>
            </did>
        </div>

        

           
        
            
        
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- JavaScript Logic -->
<script>
$(document).ready(function() {
  let questionCount = 0;

  // Add Question Block
  $('#addQuestionBtn').click(function() {
    questionCount++;
    const html = `
      <div class="border rounded p-3 mb-3 question-block">
        <div class="d-flex justify-content-between align-items-center">
          <h6>Question ${questionCount}</h6>
          <button type="button" class="btn btn-danger btn-sm removeQuestion"><i class="fas fa-trash"></i></button>
        </div>
        <div class="mb-3">
          <label class="form-label">Question Text</label>
          <input type="text" name="questions[${questionCount}][text]" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Question Type</label>
          <select name="questions[${questionCount}][type]" class="form-select question-type" required>
            <option value="rating">Rating (1–5)</option>
            <option value="text">Text Response</option>
            <option value="multiple">Multiple Choice</option>
          </select>
        </div>
        <div class="option-section mb-3" style="display:none;">
          <label class="form-label">Options (comma-separated)</label>
          <input type="text" name="questions[${questionCount}][options]" class="form-control" placeholder="e.g., Excellent,Good,Average,Poor">
        </div>
      </div>
    `;
    $('#questionContainer').append(html);
  });

  // Remove Question Block
  $(document).on('click', '.removeQuestion', function() {
    $(this).closest('.question-block').remove();
  });

  // Show/Hide Options Input
  $(document).on('change', '.question-type', function() {
    const selectedType = $(this).val();
    const optionSection = $(this).closest('.question-block').find('.option-section');
    if (selectedType === 'multiple') {
      optionSection.show();
    } else {
      optionSection.hide();
    }
  });

  // Submit Questions via AJAX
  $('#questionForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
      url: 'save_questions.php',
      type: 'POST',
      data: $(this).serialize(),
      success: function(response) {
        Swal.fire({
          icon: 'success',
          title: 'Saved Successfully!',
          text: 'Your evaluation questions have been saved.',
          confirmButtonColor: '#3085d6'
        }).then(() => window.location.href = 'manage_questions.php');
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to save questions. Please try again.',
          confirmButtonColor: '#d33'
        });
      }
    });
  });
});
</script>
