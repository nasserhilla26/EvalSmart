<?php
// organizer/manage_targets.php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // organizer
include '../includes/db_connect.php';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';

$organizer_id = (int)$_SESSION['user_id'];

// Fetch event_questionnaire entries for events owned by this organizer
$sql = "
  SELECT eq.id AS eq_id, e.event_id, e.event_title, q.questionnaire_id, q.title AS q_title, eq.assigned_at
  FROM event_questionnaire eq
  JOIN events e ON eq.event_id = e.event_id
  JOIN questionnaire q ON eq.questionnaire_id = q.questionnaire_id
  WHERE e.organizer_id = ?
  ORDER BY e.event_date DESC, eq.assigned_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $organizer_id);
$stmt->execute();
$res = $stmt->get_result();
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Manage Targets (Department / Program / Position)</h1>

  <div class="card shadow mb-4">
    <div class="card-body table-responsive">
      <table id="targetAssignmentsTable" class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Event</th>
            <th>Questionnaire</th>
            <th>Assigned On</th>
            <th>Targets</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php $i = 1; while ($row = $res->fetch_assoc()): ?>
          <tr>
            <td><?= $i++; ?></td>
            <td><?= htmlspecialchars($row['event_title']); ?></td>
            <td><?= htmlspecialchars($row['q_title']); ?></td>
            <td><?= date("F j, Y", strtotime($row['assigned_at'])); ?></td>
            <td>
              <?php
                // Show a quick badge of whether targets exist
                $tq = $conn->prepare("SELECT COUNT(*) as c FROM event_questionnaire_targets WHERE event_questionnaire_id = ?");
                $tq->bind_param('i', $row['eq_id']);
                $tq->execute();
                $tr = $tq->get_result()->fetch_assoc();
                if ($tr['c'] > 0) {
                  echo "<span class='badge bg-success'>Targeted ({$tr['c']})</span>";
                } else {
                  echo "<span class='badge bg-secondary'>All / No targets</span>";
                }
              ?>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-info openTargets" data-eq-id="<?= $row['eq_id']; ?>">
                <i class="fas fa-bullseye"></i> Manage Targets
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Targets Modal (re-used here) -->
<div class="modal fade" id="targetsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="targetsForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manage Targets</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="eq_id" name="event_questionnaire_id" value="">

        <div id="targetsList" class="mb-3"></div>

        <div class="d-flex justify-content-between">
          <small class="text-muted">Add target groups. Each row is one target (Dept / Program / Position).</small>
          <button type="button" id="addTargetBtn" class="btn btn-sm btn-outline-secondary">Add Target</button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Save Targets</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </form>
  </div>
</div>

<!-- hidden template -->
<div id="targetRowTemplate" style="display:none;">
  <div class="target-row row gx-2 align-items-center mb-2">
    <div class="col-sm-4">
      <select class="form-select dept-select">
        <option value="ALL">ALL</option>
        <option value="HED">HED</option>
        <option value="BED">BED</option>
        <option value="Offices">Offices</option>
      </select>
    </div>
    <div class="col-sm-4">
      <select class="form-select prog-select"><option value="ALL">ALL</option></select>
    </div>
    <div class="col-sm-3">
      <select class="form-select pos-select"><option value="ALL">ALL</option></select>
    </div>
    <div class="col-sm-1 text-end">
      <button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="fa fa-times"></i></button>
    </div>
    <input type="hidden" class="target-id" value="">
  </div>
</div>

<?php
include '../includes/footer.php';
?>

<script>
$(document).ready(function(){
  $('#targetAssignmentsTable').DataTable({ pageLength: 10 });

  // Program/position lists
  const programs = {
    'HED': ['ALL','BSN','BSIT','BLIS','BSTM','BSHM','BSBA','BEED'],
    'BED': ['ALL','SHS','JHS','GS'],
    'Offices': ['ALL','POD','SAC','Guidance','REC','Research'],
    'ALL': ['ALL']
  };
  const positions = {
    'HED': ['ALL','Program Heads','Faculty','Student'],
    'BED': ['ALL','Program Heads','Faculty','Student'],
    'Offices': ['ALL','Faculty','NTP'],
    'ALL': ['ALL']
  };

  function makeRow(target) {
    const $tmpl = $($('#targetRowTemplate').html());
    if (target && target.id) $tmpl.find('.target-id').val(target.id);

    const dept = (target && target.department) ? target.department : 'ALL';
    $tmpl.find('.dept-select').val(dept);

    const progSelect = $tmpl.find('.prog-select').empty();
    const progs = programs[dept] || programs['ALL'];
    progs.forEach(p => progSelect.append($('<option>').val(p).text(p)));
    const progVal = (target && target.program) ? target.program : 'ALL';
    progSelect.val(progVal);

    const posSelect = $tmpl.find('.pos-select').empty();
    const poss = positions[dept] || positions['ALL'];
    poss.forEach(p => posSelect.append($('<option>').val(p).text(p)));
    const posVal = (target && target.position) ? target.position : 'ALL';
    posSelect.val(posVal);

    return $tmpl;
  }

  // add row
  $('#addTargetBtn').on('click', function(){ $('#targetsList').append(makeRow()); });

  // dept change -> populate program & position
  $(document).on('change', '.dept-select', function(){
    const dept = $(this).val();
    const row = $(this).closest('.target-row');
    const progSelect = row.find('.prog-select').empty();
    (programs[dept] || programs['ALL']).forEach(p => progSelect.append($('<option>').val(p).text(p)));
    const posSelect = row.find('.pos-select').empty();
    (positions[dept] || positions['ALL']).forEach(p => posSelect.append($('<option>').val(p).text(p)));
  });

  // remove row (if existing target has id -> call delete_target.php)
  $(document).on('click', '.remove-row', function(){
    const row = $(this).closest('.target-row');
    const tid = row.find('.target-id').val();
    if (tid) {
      Swal.fire({
        title: 'Delete target?',
        text: 'This will remove the target row permanently.',
        icon: 'warning',
        showCancelButton: true
      }).then(res=>{
        if (res.isConfirmed) {
          $.post('delete_target.php', { target_id: tid }, function(resp){
            if (resp.success) {
              row.remove();
              Swal.fire('Deleted', resp.message, 'success');
            } else {
              Swal.fire('Error', resp.message, 'error');
            }
          }, 'json').fail(()=> Swal.fire('Error','Server error','error'));
        }
      });
    } else {
      row.remove();
    }
  });

  // ---------- WHERE to put the snippet you asked ----------
  // The snippet below is the exact handler that opens the modal and loads targets.
  // Put this inside this page's <script> block (here is the right place).
  $(document).on('click', '.openTargets', function() {
      const eq_id = $(this).data('eq-id');

      $('#eq_id').val(eq_id);
      $('#targetsList').empty();

      $.getJSON('load_targets.php', { eq_id: eq_id }, function(resp) {
          if (!resp.success || resp.targets.length === 0) {
              $('#targetsList').append(makeRow());
          } else {
              resp.targets.forEach(t => $('#targetsList').append(makeRow(t)));
          }
          $('#targetsModal').modal('show');
      }).fail(function(){
          // fallback: show one blank row
          $('#targetsList').append(makeRow());
          $('#targetsModal').modal('show');
      });
  });
  // --------------------------------------------------------

  // Submit (replace all targets)
  $('#targetsForm').on('submit', function(e){
    e.preventDefault();
    const eq_id = $('#eq_id').val();
    const targets = [];
    $('#targetsList .target-row').each(function(){
      targets.push({
        department: $(this).find('.dept-select').val() || 'ALL',
        program: $(this).find('.prog-select').val() || 'ALL',
        position: $(this).find('.pos-select').val() || 'ALL'
      });
    });

    $.post('update_targets.php', { event_questionnaire_id: eq_id, targets: JSON.stringify(targets) }, function(resp){
      if (resp.success) {
        Swal.fire('Saved', resp.message, 'success').then(()=> location.reload());
      } else {
        Swal.fire('Error', resp.message, 'error');
      }
    }, 'json').fail(()=> Swal.fire('Error','Server error','error'));
  });

});
</script>
