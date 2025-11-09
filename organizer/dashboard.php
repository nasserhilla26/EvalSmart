<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // only organizer

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
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
              <li class="list-group-item"><a href="my_events.php"><i class="fas fa-list"></i> My Events</a></li>
              <li class="list-group-item"><a href="results.php"><i class="fas fa-poll"></i> View Evaluation Results</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>

  
</div>
<!-- /.container-fluid -->

<?php include '../includes/footer.php'; ?>
