<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // Organizer only

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';


$scales = mysqli_query($conn, "
    SELECT *
    FROM evaluation_scales
    WHERE status = 'Active'
    ORDER BY scale_name ASC
");

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

        <!-- Scale Options -->
         <div class="mb-3">
            <label class="form-label fw-bold">
              Evaluation Likert Scale:
            </label>

            <select
              name="scale_id"
              class="form-select"
              required>

              <option value="">
                Select Scale
              </option>

              <?php while($scale = mysqli_fetch_assoc($scales)): ?>

                <option value="<?php echo $scale['scale_id']; ?>">
                  <?php echo htmlspecialchars($scale['scale_name']); ?>
                </option>

              <?php endwhile; ?>

            </select>

            <small class="text-muted">
              This scale will be used for all Rating questions in this questionnaire.
            </small>
          </div>


        <div class="card mb-3">
          <div class="card-header">
            <strong>Question Categories</strong>
          </div>
          <div class="card-body">

            <div id="categoryContainer"></div>

            <button type="button" class="btn btn-info" id="addCategoryBtn">
              <i class="fas fa-folder-plus"></i> Add Category
            </button>

          </div>
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


<!-- JavaScript Logic -->
<script>
$(document).ready(function() {
  let questionCount = 0;

  

  // Add Question Block
  $('#addQuestionBtn').click(function() {
    questionCount++;
    const html = `
      <div class="shadow rounded p-3 mb-3 question-block">
        <div class="d-flex justify-content-between align-items-center">
          <h6>Question ${questionCount}</h6>
          <button type="button" class="btn btn-danger btn-sm removeQuestion"><i class="fas fa-trash"></i></button>
        </div>

        <div class="mb-3">

        <div class="mb-3">
          <label class="form-label">Category</label>

          <select
              name="questions[${questionCount}][category]"
              class="form-select category-select">

              <option value="">General</option>

          </select>
        </div>

          <label class="form-label">Question Text</label>
          <input type="text" name="questions[${questionCount}][text]" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Question Type</label>
          <select name="questions[${questionCount}][type]" class="form-select question-type" required>
            <option value="rating">Likert Scale (Rating)</option>
            <option value="text">Text Response</option>
            
          </select>
        </div>

        <div class="option-section mb-3" style="display:none;">
          <label class="form-label">Options (comma-separated)</label>
          <input type="text" name="questions[${questionCount}][options]" class="form-control" placeholder="e.g., Excellent,Good,Average,Poor">
        </div>
      </div>
    `;
    
    $('#questionContainer').append(html);

    // Refresh category dropdowns for newly added question
    refreshCategoryDropdowns();


  });

  //  Remove Question
  $(document).on('click', '.removeQuestion', function() {
    $(this).closest('.question-block').remove();
  });

  //  Toggle Option Field for Multiple Choice
  $(document).on('change', '.question-type', function() {
    const selectedType = $(this).val();
    const optionSection = $(this).closest('.question-block').find('.option-section');
    if (selectedType === 'multiple') {
      optionSection.show();
    } else {
      optionSection.hide();
    }
  });

  //  Submit Questionnaire
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

  // add category
  let categoryCount = 0;

  $('#addCategoryBtn').click(function() {

      categoryCount++;

      $('#categoryContainer').append(`
          <div class="input-group mb-2 category-row">
              <input type="text"
                    name="categories[]"
                    class="form-control"
                    placeholder="Category Name (Venue, Food, Speaker)"
                    required>

              <button type="button"
                      class="btn btn-danger removeCategory">
                  <i class="fas fa-trash"></i>
              </button>
          </div>
      `);

      refreshCategoryDropdowns();

  });

// remove category
$(document).on('click', '.removeCategory', function() {

    $(this).closest('.category-row').remove();

    refreshCategoryDropdowns();
});

  // Refresh dropdowns while typing category names
$(document).on('input', 'input[name="categories[]"]', function() {
    refreshCategoryDropdowns();
});

  

// Auto populate category dropdowns
function refreshCategoryDropdowns() {

    $('.category-select').each(function() {

        let currentValue = $(this).val();

        let options = '<option value="">General</option>';

        $('input[name="categories[]"]').each(function() {

            let val = $(this).val().trim();

            if (val !== '') {
                options += `<option value="${val}">${val}</option>`;
            }
        });

        $(this).html(options);

        // Restore previous selection if still exists
        if (currentValue) {
            $(this).val(currentValue);
        }
    });
}

  
refreshCategoryDropdowns();

});




</script>
