<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // Organizer only

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

$organizer_id = $_SESSION['user_id'];

// Fetch all questionnaires created by this organizer
// $query = "
//   SELECT q.*, COUNT(qq.question_id) AS total_questions
//   FROM questionnaire q
//   LEFT JOIN questionnaire_questions qq ON q.questionnaire_id = qq.questionnaire_id
//   WHERE q.created_by = '$organizer_id'
//   GROUP BY q.questionnaire_id
//   ORDER BY q.created_at DESC;
// ";

$query = "
SELECT 
    q.questionnaire_id,
    q.title,
    q.description,
    q.created_by,
    q.created_at,
    (
        SELECT COUNT(*) 
        FROM questionnaire_questions qq 
        WHERE qq.questionnaire_id = q.questionnaire_id
    ) AS total_questions,
    (
        SELECT GROUP_CONCAT(DISTINCT e.event_title SEPARATOR ', ')
        FROM event_questionnaire eq
        JOIN events e ON eq.event_id = e.event_id
        WHERE eq.questionnaire_id = q.questionnaire_id
    ) AS assigned_events
FROM questionnaire q
WHERE q.created_by = '$organizer_id'
ORDER BY q.created_at DESC;
";




$result = mysqli_query($conn, $query);
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Manage Questionnaires</h1>

  <div class="text-end mb-3">
    <a href="create_questionnaire.php" class="btn btn-primary">
      <i class="fas fa-plus"></i> Create New Questionnaire
    </a>
  </div>

  <div class="card shadow mb-4">
    <div class="card-body table-responsive">
      <table id="questionnaireTable" class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Title</th>
            <th>Description</th>
            <th>Questions</th>
            <th>Assigned To</th>
            <th>Created At</th>
            <th width="180">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $i = 1;
          while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($row['title']); ?></td>
              <td><?php echo htmlspecialchars($row['description']); ?></td>
              <td><span class="badge bg-info"><?php echo $row['total_questions']; ?></span></td>

              <!-- <td>
                <?php if (!empty($row['assigned_events'])): ?>
                    <?php 
                    $events = explode(',', $row['assigned_events']);
                    foreach ($events as $event):
                    ?>
                    <span class="badge bg-success"><?php echo htmlspecialchars(trim($event)); ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="badge bg-secondary">Unassigned</span>
                <?php endif; ?>
                </td> -->

                <td>
                <?php if (!empty($row['assigned_events'])): ?>
                    <?php 
                    // get events for unlink buttons
                    $events = mysqli_query($conn, "
                        SELECT e.event_id, e.event_title 
                        FROM event_questionnaire eq
                        JOIN events e ON eq.event_id = e.event_id
                        WHERE eq.questionnaire_id = '{$row['questionnaire_id']}'
                    ");

                    while ($ev = mysqli_fetch_assoc($events)): ?>
                        <span class="badge bg-success me-1">
                        <?php echo htmlspecialchars($ev['event_title']); ?>
                        <button 
                            type="button" 
                            class="btn btn-sm text-light btn-unlink ms-1" 
                            data-event="<?php echo $ev['event_id']; ?>" 
                            data-questionnaire="<?php echo $row['questionnaire_id']; ?>"
                            title="Unlink from this event">
                            <i class="fas fa-times"></i>
                        </button>
                        </span>
                    <?php endwhile; ?>
                <?php else: ?>
                    <span class="badge bg-secondary">Unassigned</span>
                <?php endif; ?>
                </td>


              <td><?php echo date("F j, Y", strtotime($row['created_at'])); ?></td>
              <td class="text-center">
                <!-- View -->
                <button 
                type="button" 
                class="btn btn-sm btn-info viewBtn"
                data-id="<?php echo $row['questionnaire_id']; ?>"
                title="View Questionnaire">
                <i class="fas fa-eye"></i>
                </button>

                <!-- Edit -->
                <a href="edit_questionnaire.php?id=<?php echo $row['questionnaire_id']; ?>" 
                   class="btn btn-sm btn-warning" title="Edit Questionnaire">
                  <i class="fas fa-edit"></i>
                </a>

                <!-- Assign -->
                <a href="#" 
                class="btn btn-sm btn-secondary assignBtn" 
                data-id="<?php echo $row['questionnaire_id']; ?>" 
                data-title="<?php echo htmlspecialchars($row['title']); ?>"
                title="Assign to Event">
                <i class="fas fa-link"></i>
                </a>


                <!-- Delete --> 
                <button 
                  type="button"
                  class="btn btn-sm btn-danger deleteBtn"
                  data-id="<?php echo $row['questionnaire_id']; ?>"
                  title="Delete Questionnaire">
                  <i class="fas fa-trash"></i>
                </button>

              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<!-- Assign Questionnaire Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title" id="assignModalLabel">Assign Questionnaire</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="assignForm">
        <div class="modal-body">
          <input type="hidden" name="questionnaire_id" id="assign_questionnaire_id">

          <div class="mb-3">
            <label class="form-label fw-bold">Questionnaire:</label>
            <input type="text" class="form-control" id="assign_questionnaire_title" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Select Event:</label>
            <select class="form-select" name="event_id" id="event_id" required>
              <option value="">Loading events...</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-link"></i> Assign</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- View Questionnaire Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="viewModalLabel">View Questionnaire</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <h5 id="view_title" class="fw-bold mb-2"></h5>
        <p id="view_description" class="text-muted"></p>

        <hr>
        <h6 class="fw-bold">Questions:</h6>
        <div id="question_list"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>



<?php include '../includes/footer.php'; ?>


<script>
$(document).ready(function() {
  // Initialize DataTable
  $('#questionnaireTable').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    order: [[4, 'desc']]
  });


  // 🗑 Delete Questionnaire
$(document).on('click', '.deleteBtn', function() {
  e.preventDefault(); // Prevent <a> from navigating
  const id = $(this).data('id');
  
  Swal.fire({
    title: 'Delete Questionnaire?',
    text: 'This action cannot be undone!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'Yes, delete it'
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: 'delete_questionnaire.php',
        type: 'POST',
        data: { id: id },
        success: function(response) {
          Swal.fire({
            icon: 'success',
            title: 'Deleted!',
            text: response
          }).then(() => location.reload());
        },
        error: function() {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to delete questionnaire.'
          });
        }
      });
    }
  });
});



// View Questionnaire (Modal)
$(document).on('click', '.viewBtn', function() {
  const id = $(this).data('id');

  $.ajax({
    url: 'fetch_questionnaire_details.php',
    type: 'GET',
    data: { id: id },
    dataType: 'json',
    success: function(data) {
      if (data.success) {
        $('#view_title').text(data.title);
        $('#view_description').text(data.description);

        let questionsHtml = '';
        if (data.questions.length > 0) {
          data.questions.forEach((q, index) => {
            questionsHtml += `
              <div class="border rounded p-3 mb-2">
                <strong>Q${index + 1}:</strong> ${q.text}<br>
                <small class="text-muted">Type: ${q.type}</small>
                ${q.options ? `<br><small>Options: ${q.options.join(', ')}</small>` : ''}
              </div>
            `;
          });
        } else {
          questionsHtml = `<p class="text-muted">No questions available.</p>`;
        }

        $('#question_list').html(questionsHtml);
        $('#viewModal').modal('show');
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message
        });
      }
    },
    error: function() {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to fetch questionnaire details.'
      });
    }
  });
});





  // 🔗 Open Assign Modal
$('.assignBtn').on('click', function() {
  const questionnaireId = $(this).data('id');
  const questionnaireTitle = $(this).data('title');

  $('#assign_questionnaire_id').val(questionnaireId);
  $('#assign_questionnaire_title').val(questionnaireTitle);

  // Load organizer's events dynamically via AJAX
  $.ajax({
    url: 'fetch_organizer_events.php',
    type: 'GET',
    success: function(data) {
      $('#event_id').html(data); // populate dropdown with <option> list
      $('#assignModal').modal('show'); // show modal after successful load
    },
    error: function() {
      $('#event_id').html('<option value="">Error loading events</option>');
      $('#assignModal').modal('show'); // still show modal so user sees error
    }
  });
});



  // 💾 Handle Assign Form Submission
  $('#assignForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
      url: 'save_assigned_questionnaire.php',
      type: 'POST',
      data: $(this).serialize(),
      success: function(response) {
        $('#assignModal').modal('hide');
        Swal.fire({
          icon: 'success',
          title: 'Assigned Successfully!',
          text: response,
          confirmButtonColor: '#3085d6'
        }).then(() => location.reload());
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to assign questionnaire.',
          confirmButtonColor: '#d33'
        });
      }
    });
  });
});


// 🗑️ Unlink Questionnaire from Event
$(document).on('click', '.btn-unlink', function() {
  const eventId = $(this).data('event');
  const questionnaireId = $(this).data('questionnaire');

  Swal.fire({
    title: 'Unlink Questionnaire?',
    text: 'This will remove the questionnaire from the event.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, unlink it',
    confirmButtonColor: '#d33'
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: 'unlink_questionnaire.php',
        type: 'POST',
        data: { event_id: eventId, questionnaire_id: questionnaireId },
        success: function(response) {
          Swal.fire({
            icon: 'info',
            title: 'Updated',
            text: response
          }).then(() => location.reload());
        },
        error: function() {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Unable to unlink questionnaire.'
          });
        }
      });
    }
  });
});



</script>
