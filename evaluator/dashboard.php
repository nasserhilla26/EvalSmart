<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(3); // Evaluator only

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

?>

<!-- Begin Page Content -->
<div class="container-fluid">

  <!-- Page Heading -->
  <h1 class="h3 mb-4 text-gray-800">
    Dashboard
  </h1>





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
              <li class="list-group-item"><a href="evaluate.php"><i class="fas fa-edit"></i> Evaluate Events</a></li>
              <li class="list-group-item"><a href="my_evaluations.php"><i class="fas fa-history"></i> My Evaluations</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>


<?php

    $user_id = $_SESSION['user_id'];

//Fetch all events that have assigned questionnaires
$query = "
  SELECT 
    e.event_id,
    e.event_title,
    e.event_date,
    e.event_description,
    q.title AS questionnaire_title,
    q.questionnaire_id,
    (
      SELECT COUNT(*) 
      FROM evaluation_answers ea 
      WHERE ea.event_id = e.event_id AND ea.user_id = '$user_id'
    ) AS already_evaluated
  FROM events e
  JOIN event_questionnaire eq ON e.event_id = eq.event_id
  JOIN questionnaire q ON eq.questionnaire_id = q.questionnaire_id
  ORDER BY e.event_date DESC
";

$result = mysqli_query($conn, $query);


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