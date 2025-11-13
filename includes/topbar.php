<?php
include 'db_connect.php';

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


    <?php
    $user_id = $_SESSION['active_role'];
    // $notifQuery = mysqli_query($conn, "
    //   SELECT * FROM notifications WHERE user_id='$user_id' ORDER BY created_at DESC LIMIT 5
    // ");
    // $unreadCount = mysqli_num_rows(mysqli_query($conn, "
    //   SELECT * FROM notifications WHERE user_id='$user_id' AND is_read=0
    // "));
    ?> 

    <li class="nav-item dropdown no-arrow mx-1">
      <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-bell fa-fw"></i>
        <?php // if ($unreadCount > 0): ?> 
          <span id="notifCount" class="badge bg-danger badge-counter"></span>
        <?php //endif; ?>
      </a>

      <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="alertsDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
        <h6 class="dropdown-header">Notifications</h6>

        <!-- This is the JS target container -->
        <div id="notificationList" class="dropdown-list"></div>

        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-center small text-gray-500" href="#">View All</a>
      </div>
    </li>

    

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

            <div class="dropdown-divider"></div>
          <?php endif; ?>
          <?php endforeach; ?>
          <?php endif; ?>
        

          

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
    console.log('Switch Role Response:', data); // Debug check

    if (data.success && data.redirect) {
      Swal.fire({
        title: 'Switched!',
        text: `Now acting as "${roleName}".`,
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      }).then(() => {
        console.log('Redirecting to:', data.redirect); // Debug check
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


// Notification trigger
document.getElementById('alertsDropdown').addEventListener('click', async () => {
  await fetch('../includes/mark_notifications_read.php');
  loadNotifications();
});


async function loadNotifications() {
  const response = await fetch('../includes/fetch_notifications.php');
  const data = await response.json();

  if (!data.success) return;

  const notifList = document.getElementById('notificationList');
  const notifBadge = document.getElementById('notifCount');

  notifList.innerHTML = '';

  if (notifBadge) {
  notifBadge.textContent = data.unread_count > 0 ? data.unread_count : '';
} // fuck this line. no notif when not commented out haha

  if (data.notifications.length === 0) {
    notifList.innerHTML = `<p class="text-center text-muted p-2 m-0">No new notifications</p>`;
    return;
  }

  data.notifications.forEach(notif => {
    const item = document.createElement('a');
    item.href = notif.link || '#';
    item.className = 'dropdown-item d-flex align-items-start small';
    item.innerHTML = `
      <div class="me-2">
        <i class="fas fa-circle ${notif.is_read ? 'text-secondary' : 'text-primary'}"></i>
      </div>
      <div>
        <div class="fw-bold">${notif.title}</div>
        <div>${notif.message}</div>
        <small class="text-muted">${notif.created_at}</small>
      </div>
    `;
    notifList.appendChild(item);
  });
}

// Load notifications on page load
document.addEventListener('DOMContentLoaded', loadNotifications);
setInterval(loadNotifications, 15000); //auto-refresh every 15secs for notif


</script>
