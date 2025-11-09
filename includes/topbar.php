<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
<div id="content">

  <!-- Topbar -->
  <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
      <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">

      <!-- Divider -->
      <div class="topbar-divider d-none d-sm-block"></div>


      <!-- User Information Dropdown -->
      <li class="nav-item dropdown no-arrow">
  <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
     data-bs-toggle="dropdown" aria-expanded="false">
    <span class="mx-2 d-none d-lg-inline text-gray-600 medium">
      <?php echo $_SESSION['full_name']; ?>
    </span>
    <i class="fas fa-user-circle fa-lg"></i>
  </a>

  <!-- Dropdown menu should be a <div> -->
  <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in"
       aria-labelledby="userDropdown">
    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
      <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i>
      Logout
    </a>
  </div>
</li>

    </ul>
  </nav>
  <!-- End of Topbar -->


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
