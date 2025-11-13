<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // Organizer
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

$organizer_id = $_SESSION['user_id'];
// $events = mysqli_query($conn, "
//     SELECT DISTINCT e.event_id, e.event_title, q.title
//     FROM events e
//     JOIN evaluation_answers ea ON e.event_id = ea.event_id
//     JOIN event_questionnaire eq ON e.event_id = eq.event_id
//     JOIN questionnaire q ON eq.questionnaire_id = q.questionnaire_id
//     WHERE e.organizer_id = '$organizer_id'
//       AND q.created_by = '$organizer_id'
//     ORDER BY e.event_date DESC
// ");

?>

<div class="container-fluid mt-4">
  <h4 class="mb-4">Evaluation Results</h4>

  <!-- 🔹 Event Selector -->
  <div class="row mb-3">
    <div class="col-md-4">
      <label class="form-label fw-bold">Select Event:</label>
      <select id="eventSelect" class="form-select">
        <option value="">-- Choose Event --</option>
        <?php
        $event_query = "
          SELECT DISTINCT e.event_id, e.event_title
          FROM events e
          JOIN event_questionnaire eq ON e.event_id = eq.event_id
          JOIN questionnaire q ON eq.questionnaire_id = q.questionnaire_id
          WHERE e.organizer_id = '$organizer_id'
          ORDER BY e.event_date DESC
        ";
        $result = mysqli_query($conn, $event_query);
        while ($row = mysqli_fetch_assoc($result)) {
          echo "<option value='{$row['event_id']}'>" . htmlspecialchars($row['event_title']) . "</option>";
        }
        ?>
      </select>
    </div>

    <!-- 🔹 Department Filter -->
    <div class="col-md-3">
      <label class="form-label fw-bold">Department:</label>
      <select id="departmentFilter" class="form-select">
        <option value="">All</option>
        <option value="HED - BSIT">HED - BSIT</option>
        <option value="HED - BSTM">HED - BSTM</option>
        <option value="BED">BED</option>
      </select>
    </div>

    <!-- 🔹 Position Filter -->
    <div class="col-md-3">
      <label class="form-label fw-bold">Position:</label>
      <select id="positionFilter" class="form-select">
        <option value="">All</option>
        <option value="Student">Student</option>
        <option value="Faculty">Faculty</option>
        <option value="NTP">NTP</option>
        <option value="Program Heads">Program Heads</option>
      </select>
    </div>
  </div>

  <!-- 🔹 Static DataTable -->
  <div class="card border-0 shadow mt-4">
    <div class="card-body">
      <div class="table-responsive">
        <table id="evaluatorTable" class="table table-bordered align-middle">
          <thead class="table-primary text-center">
            <tr>
              <th>#</th>
              <th>Evaluator</th>
              <th>Department</th>
              <th>Position</th>
              <th>Date Submitted</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="evaluatorTableBody">
            
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>


<!-- View Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Evaluator Response</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="responseModalBody">
        <p class="text-center text-muted">Loading...</p>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>


<script>
let table;

document.addEventListener('DOMContentLoaded', function () {
  table = $('#evaluatorTable').DataTable({
    pageLength: 10,
    ordering: true,
    searching: true,
    responsive: true,
    destroy: true,
    columnDefs: [{ orderable: false, targets: 5 }]
  });

  $('#eventSelect, #departmentFilter, #positionFilter').on('change', loadEvaluators);
});

async function loadEvaluators() {
  const eventId = $('#eventSelect').val();
  const dept = $('#departmentFilter').val();
  const pos = $('#positionFilter').val();

  if (!eventId) {
    table.clear().draw(); // clears DataTable visually
    return;
  }

  try {
    const response = await fetch(`./fetch_event_evaluators.php?event_id=${eventId}&department=${dept}&position=${pos}`);
    const html = (await response.text()).trim();

    // Parse the HTML rows into a temporary element
    const tempDiv = document.createElement('tbody');
    tempDiv.innerHTML = html;

    // Extract data from each <tr>
    const rows = [...tempDiv.querySelectorAll('tr')].map(tr =>
      [...tr.querySelectorAll('td')].map(td => td.innerHTML)
    );


    const trs = tempDiv.querySelectorAll('tr');

    // Check if the only row is a "no data" message (colspan row)
    if (trs.length === 1 && trs[0].querySelector('td')?.colSpan >= 6) {
      table.clear().draw(); // Clears table cleanly
      return; // Stop — no valid data rows to add
    }


    // Clear existing rows, add new ones, and redraw
    table.clear();
    if (rows.length > 0) {
      table.rows.add(rows);
    }
    table.draw();

  } catch (err) {
    console.error('Error loading evaluators:', err);
  }
}


// View Response button
async function viewResponse(userId, eventId) {
  const res = await fetch(`./fetch_event_responses.php?event_id=${eventId}&user_id=${userId}`);
  const html = await res.text();
  document.getElementById('responseModalBody').innerHTML = html;
  new bootstrap.Modal(document.getElementById('responseModal')).show();
}

</script>

