<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // Organizer
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

$organizer_id = $_SESSION['user_id'];
$events = mysqli_query($conn, "
    SELECT DISTINCT e.event_id, e.event_title, q.title
    FROM events e
    JOIN evaluation_answers ea ON e.event_id = ea.event_id
    JOIN event_questionnaire eq ON e.event_id = eq.event_id
    JOIN questionnaire q ON eq.questionnaire_id = q.questionnaire_id
    WHERE e.organizer_id = '$organizer_id'
      AND q.created_by = '$organizer_id'
    ORDER BY e.event_date DESC
");

?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Individual Evaluation Results</h1>

  <!-- Step 1: Event Selection -->
  <div class="mb-3">
    <label class="form-label">Select Event:</label>
    <select id="eventSelect" class="form-select">
      <option value="">-- Select Event --</option>
      <?php while ($e = mysqli_fetch_assoc($events)): ?>
        <option value="<?= $e['event_id'] ?>">
  <?= htmlspecialchars($e['event_title']) ?> — <?= htmlspecialchars($e['title']) ?></option>
      <?php endwhile; ?>
    </select>
  </div>

  <!-- Step 2: Evaluators Table -->
  <div id="evaluatorTableContainer" class="mt-4"></div>
</div>

<!-- Step 3: View Response Modal -->
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



document.getElementById('eventSelect').addEventListener('change', async function() {
  const eventId = this.value;
  if (!eventId) {
    document.getElementById('evaluatorTableContainer').innerHTML = '';
    return;
  }

  const res = await fetch(`fetch_event_evaluators.php?event_id=${eventId}`);
  const html = await res.text();
  document.getElementById('evaluatorTableContainer').innerHTML = html;
});

// View Response button
async function viewResponse(userId, eventId) {
  const res = await fetch(`fetch_event_responses.php?event_id=${eventId}&user_id=${userId}`);
  const html = await res.text();
  document.getElementById('responseModalBody').innerHTML = html;
  new bootstrap.Modal(document.getElementById('responseModal')).show();
}


</script>



