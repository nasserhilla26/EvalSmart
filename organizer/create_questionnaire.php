<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // Organizer only

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Create Evaluation Questionnaire</h1>

  <div class="card shadow mb-4">
    <div class="card-body">
      <form id="questionnaireForm">
        <!-- Questionnaire Details -->
        <div class="mb-3">
          <label class="form-label fw-bold">Questionnaire Title:</label>
          <input type="text" name="title" class="form-control" placeholder="Enter questionnaire title" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Description:</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Short description or purpose" required></textarea>
        </div>

        <hr>
        <h5 class="mb-3">Add Questions</h5>

        <div id="questionContainer"></div>

        <button type="button" class="btn btn-success mb-3" id="addQuestionBtn">
          <i class="fas fa-plus"></i> Add Question
        </button>

        <div class="text-end">
          <button type="submit" class="btn btn-primary">Save Questionnaire</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- SweetAlert2
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->

<!-- JavaScript Logic -->
<script>
$(document).ready(function() {
  let questionCount = 0;

  // ➕ Add Question Block
  $('#addQuestionBtn').click(function() {
    questionCount++;
    const html = `
      <div class="shadow rounded p-3 mb-3 question-block">
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

  // 🗑 Remove Question
  $(document).on('click', '.removeQuestion', function() {
    $(this).closest('.question-block').remove();
  });

  // 🧩 Toggle Option Field for Multiple Choice
  $(document).on('change', '.question-type', function() {
    const selectedType = $(this).val();
    const optionSection = $(this).closest('.question-block').find('.option-section');
    if (selectedType === 'multiple') {
      optionSection.show();
    } else {
      optionSection.hide();
    }
  });

  // 💾 Submit Questionnaire
  $('#questionnaireForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
      url: 'save_questionnaire.php',
      type: 'POST',
      data: $(this).serialize(),
      success: function(response) {
        Swal.fire({
          icon: 'success',
          title: 'Questionnaire Saved',
          text: response,
          confirmButtonColor: '#3085d6'
        }).then(() => window.location.href = 'manage_questionnaires.php');
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to save questionnaire.',
          confirmButtonColor: '#d33'
        });
      }
    });
  });
});
</script>
