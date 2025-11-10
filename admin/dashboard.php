<?php

include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1); // only admin

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


    <!-- ===================== ADMIN SECTION ===================== -->
    <div class="row">
      <div class="col-lg-6 mb-4">
        <div class="card shadow h-100 py-2">
          <div class="card-body">
            <h4 class="text-primary">Welcome, <?php echo $_SESSION['full_name']; ?>!</h4>
            <p class="text-muted mb-3">You are logged in as <strong>Administrator</strong>.</p>
            <p>As an Admin, you can manage all users, events, and view global evaluation results.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card shadow h-100 py-2">
          <div class="card-body">
            <h5 class="text-success">Quick Actions</h5>
            <ul class="list-group">
              <li class="list-group-item"><a href="review_questionnaires.php"><i class="fas fa-pen"></i> Review Questionnaires</a></li>
              <li class="list-group-item"><a href="users.php"><i class="fas fa-users"></i> Manage Users</a></li>
              <li class="list-group-item"><a href="events.php"><i class="fas fa-calendar-alt"></i> Manage Events</a></li>
              <li class="list-group-item"><a href="reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>



</div>
<!-- /.container-fluid -->

<?php include '../includes/footer.php'; ?>
