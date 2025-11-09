<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // Organizer only
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

// 🧩 Get questionnaire ID
if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid request.'); window.location='manage_questionnaires.php';</script>";
    exit;
}

$id = intval($_GET['id']);
$organizer_id = $_SESSION['user_id'];

// 🧠 Validate ownership
$query = mysqli_query($conn, "SELECT * FROM questionnaire WHERE questionnaire_id='$id' AND created_by='$organizer_id'");
if (mysqli_num_rows($query) == 0) {
    echo "<script>alert('Unauthorized access.'); window.location='manage_questionnaires.php';</script>";
    exit;
}

$questionnaire = mysqli_fetch_assoc($query);

// Fetch questions
$questions = mysqli_query($conn, "SELECT * FROM questionnaire_questions WHERE questionnaire_id='$id'");
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Edit Questionnaire</h1>

  <div class="card shadow mb-4 w-75">
    <div class="card-body">
      <form id="editQuestionnaireForm">
        <input type="hidden" name="questionnaire_id" value="<?php echo $id; ?>">

        <div class="mb-3">
          <label class="form-label fw-bold">Questionnaire Title:</label>
          <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($questionnaire['title']); ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Description:</label>
          <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($questionnaire['description']); ?></textarea>
        </div>

        <hr>
        <h5 class="mb-3">Questions</h5>

        <div id="questionContainer">
          <?php 
          $count = 0;
          while ($q = mysqli_fetch_assoc($questions)):
            $count++;
            $opts = $q['options'] ? implode(',', json_decode($q['options'], true)) : '';
          ?>
            <div class="shadow rounded p-3 mb-3 question-block">
              <div class="d-flex justify-content-between align-items-center">
                <h6>Question <?php echo $count; ?></h6>
                <button type="button" class="btn btn-danger btn-sm removeQuestion"><i class="fas fa-trash"></i></button>
              </div>

              <div class="mb-3">
                <label class="form-label">Question Text</label>
                <input type="text" name="questions[<?php echo $count; ?>][text]" class="form-control" value="<?php echo htmlspecialchars($q['question_text']); ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Question Type</label>
                <select name="questions[<?php echo $count; ?>][type]" class="form-select question-type" required>
                  <option value="rating" <?php echo ($q['question_type']=='rating')?'selected':''; ?>>Rating (1–5)</option>
                  <option value="text" <?php echo ($q['question_type']=='text')?'selected':''; ?>>Text Response</option>
                  <option value="multiple" <?php echo ($q['question_type']=='multiple')?'selected':''; ?>>Multiple Choice</option>
                </select>
              </div>

              <div class="option-section mb-3" style="display: <?php echo ($q['question_type']=='multiple')?'block':'none'; ?>;">
                <label class="form-label">Options (comma-separated)</label>
                <input type="text" name="questions[<?php echo $count; ?>][options]" class="form-control" value="<?php echo htmlspecialchars($opts); ?>">
              </div>
            </div>
          <?php endwhile; ?>
        </div>

        <button type="button" class="btn btn-success mb-3" id="addQuestionBtn">
          <i class="fas fa-plus"></i> Add Question
        </button>

        <div class="text-end">
          <a href="manage_questionnaires.php" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
  let questionCount = <?php echo $count; ?>;

  // ➕ Add new question block
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

  // 🗑 Remove question
  $(document).on('click', '.removeQuestion', function() {
    $(this).closest('.question-block').remove();
  });

  // 🧩 Toggle option input visibility
  $(document).on('change', '.question-type', function() {
    const selectedType = $(this).val();
    const optionSection = $(this).closest('.question-block').find('.option-section');
    if (selectedType === 'multiple') optionSection.show();
    else optionSection.hide();
  });

  // 💾 Submit form via AJAX
  $('#editQuestionnaireForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
      url: 'update_questionnaire.php',
      type: 'POST',
      data: $(this).serialize(),
      success: function(response) {
        Swal.fire({
          icon: 'success',
          title: 'Updated',
          text: response,
          confirmButtonColor: '#3085d6'
        }).then(() => window.location.href = 'manage_questionnaires.php');
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to update questionnaire.'
        });
      }
    });
  });
});
</script>
