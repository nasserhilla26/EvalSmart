
<!-- // Determine the correct dashboard link based on user role
$dashboardLink = "#"; // default

switch ($_SESSION['role_id']) {
  case 1:
    $dashboardLink = "../admin/dashboard.php";
    break;
  case 2:
    $dashboardLink = "../organizer/dashboard.php";
    break;
  case 3:
    $dashboardLink = "../evaluator/dashboard.php";
    break;
  default:
    $dashboardLink = "../dashboard/dashboard.php"; // fallback if needed
} -->


<?php
// -------------------- Role-based Sidebar --------------------

// Make sure session is started
if (session_status() === PHP_SESSION_NONE) session_start();

// Determine which role is active
$activeRole = $_SESSION['active_role'] ?? null;

// Assign correct dashboard link
switch ($activeRole) {
  case 1:
    $dashboardLink = "./dashboard.php";
    break;
  case 2:
    $dashboardLink = "./dashboard.php";
    break;
  case 3:
    $dashboardLink = "./dashboard.php";
    break;
  default:
    $dashboardLink = "#";
}
?>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

  <!-- Sidebar - Brand -->
  <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo $dashboardLink; ?>">
    <div class="sidebar-brand-icon rotate-n-15">
      <i class="fas fa-lightbulb"></i>
    </div>
    <div class="sidebar-brand-text mx-3">EvalSmart</div>
  </a>

  <hr class="sidebar-divider my-0">

  <!-- Dashboard Link -->
  <li class="nav-item active">
    <a class="nav-link" href="<?php echo $dashboardLink; ?>">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  <!-- ==================== ADMIN MENU ==================== -->
  <?php if ($activeRole == 1): ?>
    <div class="sidebar-heading">Admin Management</div>

    <li class="nav-item">
      <a class="nav-link" href="../admin/users.php">
        <i class="fas fa-users"></i>
        <span>Manage Users</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="../admin/events.php">
        <i class="fas fa-calendar-alt"></i>
        <span>Manage Events</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="../admin/reports.php">
        <i class="fas fa-chart-bar"></i>
        <span>Reports</span>
      </a>
    </li>
  <?php endif; ?>


  <!-- ==================== ORGANIZER MENU ==================== -->
  <?php if ($activeRole == 2): ?>
    <div class="sidebar-heading">Event Management</div>

    <li class="nav-item">
      <a class="nav-link" href="../organizer/create_event.php">
        <i class="fas fa-calendar-plus"></i>
        <span>Create Event</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="../organizer/manage_events.php">
        <i class="fas fa-list"></i>
        <span>My Events</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="../organizer/create_questionnaire.php">
        <i class="fas fa-poll"></i>
        <span>Create Questionnaire</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="../organizer/manage_questionnaires.php">
        <i class="fas fa-tasks"></i>
        <span>Manage Questionnaires</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="../organizer/view_results.php">
        <i class="fas fa-chart-pie"></i>
        <span>Evaluation Results</span>
      </a>
    </li>
  <?php endif; ?>


  <!-- ==================== EVALUATOR MENU ==================== -->
  <?php if ($activeRole == 3): ?>
    <div class="sidebar-heading">Evaluation</div>

    <!-- <li class="nav-item">
      <a class="nav-link" href="#">
        <i class="fas fa-edit"></i>
        <span>Evaluate Events</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="../evaluator/my_evaluations.php">
        <i class="fas fa-history"></i>
        <span>My Evaluations</span>
      </a>
    </li> -->
  <?php endif; ?>

  <hr class="sidebar-divider">
</ul>
<!-- End of Sidebar -->






<!-- Logout Confirmation Modal -->
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to log out of your account?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <a class="btn btn-danger" href="../logout.php">Logout</a>
        </div>
      </div>
    </div>
  </div>