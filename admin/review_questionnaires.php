<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1); // Admin only
include '../includes/db_connect.php';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';

// Fetch all questionnaires created by organizers
$result = mysqli_query($conn, "
    SELECT q.*, u.user_id, u.first_name, u.last_name 
    FROM questionnaire q 
    JOIN users u ON q.created_by = u.user_id 
    ORDER BY q.created_at DESC
");
?>

<div class="container-fluid">
  <h3 class="mb-4">Review Questionnaires</h3>

  <div class="card border-0 shadow">
    <div class="card-body table-responsive">
      <table id="eventsTable" class="table table-bordered align-middle table-hover">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Title</th>
            <th>Created By</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1;
          while ($q = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($q['title']) ?></td>
              <td><?= htmlspecialchars($q['first_name'] . " " . $q['last_name']) ?></td>
              <td>
                <span class="badge bg-<?=
                  $q['status'] === 'Approved' ? 'success' :
                  ($q['status'] === 'Modify' ? 'danger' : 'warning text-dark')
                  ?>">
                  <?= $q['status'] ?>
                </span>
              </td>
              <td>
                <!-- View Button -->
                <button type="button" class="btn btn-sm btn-info viewBtn" data-id="<?php echo $q['questionnaire_id']; ?>"
                  title="View Questionnaire">
                  <i class="fas fa-eye"></i>
                </button>

                <!-- Review Button -->
                <button class="btn btn-success btn-sm reviewBtn" data-id="<?= $q['questionnaire_id'] ?>"
                  data-title="<?= htmlspecialchars($q['title']) ?>" data-status="<?= $q['status'] ?>"
                  data-comment="<?= htmlspecialchars($q['admin_comment'] ?? '') ?>">
                  <i class="fas fa-edit"></i>
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="reviewForm" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Review Questionnaire</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="questionnaire_id" id="q_id">
        <p><strong>Title:</strong> <span id="q_title"></span></p>

        <div class="mb-3">
          <label>Status</label>
          <select name="status" id="q_status" class="form-select" required>
            <option value="Pending">Pending</option>
            <option value="Modify">Modify</option>
            <option value="Approved">Approved</option>
          </select>
        </div>

        <div class="mb-3">
          <label>Comment</label>
          <textarea name="admin_comment" id="q_comment" class="form-control" rows="2" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Save</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
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

        <div class="mb-3">
          <label class="fw-bold">
            Evaluation Scale:
          </label>

          <div id="view_scale"></div>
        </div>

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

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>


  $(document).ready(function () {


    $('#eventsTable').DataTable({
      pageLength: 10,
      lengthMenu: [5, 10, 25, 50],
      order: [[2, 'desc']]
    });


    // ================== Review Modal ==================
    document.querySelectorAll('.reviewBtn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('q_id').value = btn.dataset.id;
        document.getElementById('q_title').textContent = btn.dataset.title;
        document.getElementById('q_status').value = btn.dataset.status;
        document.getElementById('q_comment').value = btn.dataset.comment;
        new bootstrap.Modal(document.getElementById('reviewModal')).show();
      });
    });

    // ================== Submit Review ==================
    document.getElementById('reviewForm').addEventListener('submit', async function (e) {
      e.preventDefault();

      const formData = new FormData(this);

      const response = await fetch('update_questionnaire_status.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Updated!',
          text: data.message,
          timer: 1500,
          showConfirmButton: false
        }).then(() => location.reload());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message
        });
      }
    });




    // View Questionnaire (Modal)
    $(document).on('click', '.viewBtn', function () {
      const id = $(this).data('id');

      $.ajax({
        url: 'view_questionnaire.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function (data) {
          if (data.success) {
            $('#view_title').text(data.title);
            $('#view_description').text(data.description);

            $('#view_scale').html(`
              <span class="badge bg-dark">
                  ${data.scale_name ?? 'No Scale'}
              </span>
          `);

            let questionsHtml = '';

            Object.keys(data.questions).forEach(category => {

              questionsHtml += `
                <div class="mb-3">
                    <h6 class="fw-bold text-primary">
                        <i class="fas fa-folder-open"></i>
                        ${category}
                    </h6>
                    <ul class="list-group">
            `;

              data.questions[category].forEach(q => {

                questionsHtml += `
                    <li class="list-group-item">
                        <strong>${q.text}</strong><br>

                        <small class="text-muted">
                            Type: ${q.type}
                        </small>
                `;

                if (q.options) {

                  questionsHtml += `
                        <br>
                        <small>
                            Options:
                            ${q.options.join(', ')}
                        </small>
                    `;
                }

                questionsHtml += `</li>`;
              });

              questionsHtml += `
                    </ul>
                </div>
            `;
            });


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
        error: function () {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to fetch questionnaire details.'
          });
        }
      });
    });




  });
</script>