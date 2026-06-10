<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(3); // Evaluator only

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';


$user_id = $_SESSION['user_id'];

$pendingOrganizer = false;

// Check if user has organizer role
$checkOrg = mysqli_query($conn, "
    SELECT * FROM user_roles 
    WHERE user_id = $user_id AND role_id = 2
");

// Check if pending
if (mysqli_num_rows($checkOrg) > 0) {
    $userCheck = mysqli_query($conn, "
        SELECT role_status FROM users WHERE user_id = $user_id
    ");
    $u = mysqli_fetch_assoc($userCheck);

    if ($u['role_status'] === 'Pending') {
        $pendingOrganizer = true;
    }
}


?>

<!-- Begin Page Content -->
<div class="container-fluid">

  <!-- Page Heading -->
  <h1 class="h3 mb-4 text-gray-800">
    Dashboard
  </h1>


  <?php if ($pendingOrganizer): ?>
    <div class="alert alert-warning shadow-sm d-flex align-items-center" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <div>
        Your organizer account is still <strong>pending approval</strong>. 
        You are currently logged in as <strong>Evaluator</strong>.
      </div>
    </div>
  <?php endif; ?>


    <!-- ===================== EVALUATOR SECTION ===================== -->
    <div class="row">
      <div class="col-lg-6 mb-4">
        <div class="card shadow h-100 py-2">
          <div class="card-body">
            <h4 class="text-primary">Welcome, <?php echo $_SESSION['full_name']; ?>!</h4>
            <p class="text-muted mb-3">You are logged in as an <strong>Evaluator</strong>.</p>
            <p>You can participate in evaluating events assigned to you and view your submitted evaluations.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card shadow h-100 py-2">
          <div class="card-body">
            <h5 class="text-success">Quick Actions</h5>
            <ul class="list-group">
              <li class="list-group-item"><a href="#"><i class="fas fa-edit"></i> Evaluate Events</a></li>
              <li class="list-group-item"><a href="#"><i class="fas fa-history"></i> My Evaluations</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>


<?php

$user_id = $_SESSION['user_id'];

$dept_full = $_SESSION['department'];  // e.g. "HED - BSIT"
$pos_val   = $_SESSION['position'];    // Student / Faculty / Program Heads / NTP

$dept_val = 'ALL';
$prog_val = 'ALL';

if (strpos($dept_full, ' - ') !== false) {
    list($dept_val, $prog_val) = array_map('trim', explode(' - ', $dept_full));
} else {
    // Offices like "Offices - POD" also split here
    $dept_val = trim($dept_full);
}



$sql = "
SELECT 
    e.event_id,
    e.event_title,
    e.event_date,
    e.event_description,
    q.title AS questionnaire_title,
    eq.id AS eq_id,
    (
        SELECT COUNT(*) 
        FROM evaluation_answers ea 
        WHERE ea.event_id = e.event_id 
          AND ea.user_id = ?
    ) AS already_evaluated
FROM events e
JOIN event_questionnaire eq 
    ON e.event_id = eq.event_id
JOIN questionnaire q 
    ON q.questionnaire_id = eq.questionnaire_id
WHERE
(
    -- CASE 1: No targets exist → visible to ALL
    NOT EXISTS (
        SELECT 1 
        FROM event_questionnaire_targets t 
        WHERE t.event_questionnaire_id = eq.id
    )

    OR

    -- CASE 2: Match one of the target rows
    EXISTS (
        SELECT 1
        FROM event_questionnaire_targets t
        WHERE t.event_questionnaire_id = eq.id
          AND (t.department = 'ALL' OR t.department = ?)
          AND (t.program    = 'ALL' OR t.program = ?)
          AND (t.position   = 'ALL' OR t.position = ?)
    )
)
ORDER BY e.event_date DESC
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $user_id, $dept_val, $prog_val, $pos_val);
$stmt->execute();
$result = $stmt->get_result();


?>

  <div class="card shadow mb-4">
    <div class="card-body table-responsive">
      <table id="eventsTable" class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Event Title</th>
            <th>Date</th>
            <th>Questionnaire</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $i = 1;
          while ($row = mysqli_fetch_assoc($result)): 
          ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($row['event_title']); ?></td>
              <td><?php echo date("F j, Y", strtotime($row['event_date'])); ?></td>
              <td><?php echo htmlspecialchars($row['questionnaire_title']); ?></td>
              <td>
                <?php if ($row['already_evaluated'] > 0): ?>
                  <span class="badge bg-success">Completed</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Pending</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($row['already_evaluated'] > 0): ?>
                  <button class="btn btn-sm btn-secondary" disabled>
                    <i class="fas fa-check"></i> Evaluated
                  </button>
                <?php else: ?>
                  <a href="event_evaluate.php?id=<?php echo $row['event_id']; ?>" 
                     class="btn btn-sm btn-primary">
                     <i class="fas fa-edit"></i> Evaluate
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>


</div>
<!-- /.container-fluid -->

<?php include '../includes/footer.php'; ?>



<script>
$(document).ready(function() {
  $('#eventsTable').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    order: [[2, 'desc']]
  });
});
</script>