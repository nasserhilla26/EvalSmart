<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // only organizer

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';


$user_id = $_SESSION['user_id'];

// =======================
// STATS
// =======================

// My Events
$total_events = mysqli_fetch_row(mysqli_query($conn, "
  SELECT COUNT(*) FROM events WHERE organizer_id = $user_id
"))[0];

// Active Events
$active_events = mysqli_fetch_row(mysqli_query($conn, "
  SELECT COUNT(*) FROM events 
  WHERE organizer_id = $user_id AND event_date >= CURDATE()
"))[0];

// Completed Events
$completed_events = mysqli_fetch_row(mysqli_query($conn, "
  SELECT COUNT(*) FROM events 
  WHERE organizer_id = $user_id AND event_date < CURDATE()
"))[0];

// Total Evaluations (my events)
$total_evaluations = mysqli_fetch_row(mysqli_query($conn, "
  SELECT COUNT(*) 
  FROM evaluation_answers ea
  JOIN events e ON ea.event_id = e.event_id
  WHERE e.organizer_id = $user_id
"))[0];

// Recent events (limit 5)
$recent_events = mysqli_query($conn, "
  SELECT 
    e.event_id,
    e.event_title,
    e.event_date,
    COUNT(DISTINCT ea.user_id) as respondents
  FROM events e
  LEFT JOIN evaluation_answers ea ON e.event_id = ea.event_id
  WHERE e.organizer_id = $user_id
  GROUP BY e.event_id
  ORDER BY e.event_date DESC
  LIMIT 5
");

// Pack data
$data = [
  'total_events' => $total_events,
  'active_events' => $active_events,
  'completed_events' => $completed_events,
  'total_evaluations' => $total_evaluations,
  'recent_events' => $recent_events
];


?>

<!-- Begin Page Content -->
<div class="container-fluid">

  <!-- Page Heading -->
  <h1 class="h3 mb-4 text-gray-800">
    Dashboard
  </h1>



    <!-- ===================== ORGANIZER SECTION ===================== -->
    <div class="row">
      <div class="col-lg-6 mb-4">
        <div class="card shadow h-100 py-2">
          <div class="card-body">
            <h4 class="text-primary">Welcome, <?php echo $_SESSION['full_name']; ?>!</h4>
            <p class="text-muted mb-3">You are logged in as an <strong>Event Organizer</strong>.</p>
            <p>You can create, manage, and view feedback results of your organized events.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card shadow h-100 py-2">
          <div class="card-body">
            <h5 class="text-success">Quick Actions</h5>
            <ul class="list-group">
              <li class="list-group-item"><a href="create_event.php"><i class="fas fa-calendar-plus"></i> Create Event</a></li>
              <li class="list-group-item"><a href="manage_events.php"><i class="fas fa-list"></i> My Events</a></li>
              <!-- <li class="list-group-item"><a href="view_results.php"><i class="fas fa-poll"></i> View Evaluation Results</a></li> -->
            </ul>
          </div>
        </div>
      </div>
    </div>


     <?php include '../includes/dashboard/organizer_stats.php'; ?>

    <div class="row">
      <div class="col">
          <?php include '../includes/dashboard/organizer_events.php'; ?>
      </div>
    </div>

      </div>
    </div>
  
</div>
<!-- /.container-fluid -->

<?php include '../includes/footer.php'; ?>


<script>
$(document).ready(function() {
  $('#recentEventsTable').DataTable({
    pageLength: 5,
    lengthMenu: [5, 10, 25, 50],
    order: [[1, 'desc']], // Default sort by Date column
  });
});
</script>