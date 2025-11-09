<?php
$roleNames = [2=>'Organizer',3=>'Evaluator'];
$activeRoleName = $roleNames[$_SESSION['active_role']] ?? 'Unknown';
?>


<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
<div id="content">

  <!-- Topbar -->
  <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 sticky-top shadow">

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
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
        <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="userDropdown">
          
          <?php if (isset($_SESSION['active_role'])): ?>
          <h6 class="dropdown-header">Switch Role</h6>
          
          <?php foreach ($roleNames as $roleId => $roleName): ?>
          <?php if ($roleId != $_SESSION['active_role']): ?>
          <a class="dropdown-item switchRole" href="#" data-role="<?php echo $roleId; ?>">
            <i class="fas fa-exchange-alt fa-sm fa-fw me-2 text-gray-400"></i>Switch to <?php echo $roleName; ?></a>
          <?php endif; ?>
          <?php endforeach; ?>
          <?php endif; ?>
        

          <div class="dropdown-divider"></div>

          <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i>Logout
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




<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
document.addEventListener('click', async function (e) {
  if (!e.target.classList.contains('switchRole')) return;
  e.preventDefault();

  const roleId = e.target.dataset.role;
  const roleName = e.target.textContent.trim().replace('Switch to ', '');

  const result = await Swal.fire({
    title: 'Switch Role',
    text: `Switch to "${roleName}" role?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, switch'
  });

  if (!result.isConfirmed) return;

  try {
    const res = await fetch('/evalsmart/switch_role.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'role_id=' + roleId
    });

    const data = await res.json();
    console.log('Switch Role Response:', data); // 🧠 Debug check

    if (data.success && data.redirect) {
      Swal.fire({
        title: 'Switched!',
        text: `Now acting as "${roleName}".`,
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      }).then(() => {
        console.log('Redirecting to:', data.redirect); // 🧠 Debug check
        window.location.href = data.redirect;
      });
    } else {
      Swal.fire({icon: 'error', title: 'Error', text: data.message || 'Failed to switch role.'});
    }
  } catch (err) {
    console.error('Error switching role:', err);
    Swal.fire({icon: 'error', title: 'Error', text: 'Server error occurred.'});
  }
});
</script>
