<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1);
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

// FILTER
$filter = $_GET['filter'] ?? 'all';

$where = "";
if ($filter == 'pending') {
    $where = "WHERE role_status = 'Pending'";
} elseif ($filter == 'inactive') {
    $where = "WHERE status = 'Inactive'";
}

$query = "SELECT * FROM users $where ORDER BY user_id DESC";
$result = mysqli_query($conn, $query);
?>

<div class="container-fluid">
  <div class="row">
  <div class="col"><h1 class="h3 text-gray-800">Manage Users</h1></div>
  <div class="col text-end"><a href="register.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add New User</a></div>
  </div>

  <!-- FILTER BUTTONS -->
  <div class="mb-3">
    <span>Filter: </span>
    <a href="?filter=all" class="btn btn-secondary btn-sm">All</a>
    <a href="?filter=pending" class="btn btn-warning btn-sm">Pending</a>
    <a href="?filter=inactive" class="btn btn-danger btn-sm">Inactive</a>
  </div>

  <div class="card shadow mb-4">
    <div class="card-body table-responsive">

      <table id="usersTable" class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Roles</th>
            <th>Department</th>
            <th>Position</th>
            <th>Approval</th>
            <th>Status</th>
            <th width="260">Action</th>
          </tr>
        </thead>

        <tbody>
          <?php $i=1; while($row = mysqli_fetch_assoc($result)): ?>

          <?php
          // GET ROLES
          $roles_q = mysqli_query($conn, "
            SELECT role_id FROM user_roles WHERE user_id = {$row['user_id']}
          ");

          $role_badges = '';
          $isAdmin = false;

          while ($r = mysqli_fetch_assoc($roles_q)) {
              if ($r['role_id'] == 1) {
                  $role_badges .= '<span class="badge bg-dark me-1">Admin</span>';
                  $isAdmin = true;
              }
              elseif ($r['role_id'] == 2) {
                  $role_badges .= '<span class="badge bg-primary me-1">Organizer</span>';
              }
              else {
                  $role_badges .= '<span class="badge bg-secondary me-1">Evaluator</span>';
              }
          }

          $isSelf = ($_SESSION['user_id'] == $row['user_id']);
          ?>

          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= $role_badges ?></td>
            <td><?= htmlspecialchars($row['department']) ?></td>
            <td><?= htmlspecialchars($row['position']) ?></td>

            <!-- APPROVAL -->
            <td>
              <?php if ($row['role_status'] == 'Pending'): ?>
                <span class="badge bg-warning">Pending</span>
              <?php elseif ($row['role_status'] == 'Approved'): ?>
                <span class="badge bg-success">Approved</span>
              <?php else: ?>
                <span class="badge bg-danger">Rejected</span>
              <?php endif; ?>
            </td>

            <!-- STATUS -->
            <td>
              <?php if ($row['status'] == 'Active'): ?>
                <span class="badge bg-success">Active</span>
              <?php else: ?>
                <span class="badge bg-danger">Inactive</span>
              <?php endif; ?>
            </td>

            <!-- ACTIONS -->
            <td class="text-center">

              <!-- APPROVE / REJECT -->
              <?php if ($row['role_status'] == 'Pending'): ?>
                <button class="btn btn-success btn-sm approveBtn"
                  data-id="<?= $row['user_id'] ?>"
                  data-bs-toggle="tooltip"
                  title="Approve Organizer">
                  <i class="fas fa-check"></i>
                </button>

                <button class="btn btn-danger btn-sm rejectBtn"
                  data-id="<?= $row['user_id'] ?>"
                  data-bs-toggle="tooltip"
                  title="Reject Organizer">
                  <i class="fas fa-times"></i>
                </button>
              <?php endif; ?>

              <!-- TOGGLE STATUS -->
              <?php if (!$isAdmin && !$isSelf): ?>
                <button class="btn btn-warning btn-sm toggleStatusBtn"
                  data-id="<?= $row['user_id'] ?>"
                  data-bs-toggle="tooltip"
                  title="Activate / Deactivate User">
                  <i class="fas fa-sync-alt"></i>
                </button>
              <?php else: ?>
                <button class="btn btn-secondary btn-sm" disabled
                  data-bs-toggle="tooltip"
                  title="Cannot modify admin account">
                  <i class="fas fa-lock"></i>
                </button>
              <?php endif; ?>

              <!-- DELETE -->
              <?php if (!$isAdmin && !$isSelf): ?>
                <button class="btn btn-danger btn-sm deleteUserBtn"
                  data-id="<?= $row['user_id'] ?>"
                  data-bs-toggle="tooltip"
                  title="Delete User">
                  <i class="fas fa-trash"></i>
                </button>
              <?php else: ?>
                <button class="btn btn-secondary btn-sm" disabled
                  data-bs-toggle="tooltip"
                  title="Cannot delete admin account">
                  <i class="fas fa-ban"></i>
                </button>
              <?php endif; ?>

            </td>
          </tr>

          <?php endwhile; ?>
        </tbody>
      </table>

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>


<script>
$(document).ready(function(){

  $('#usersTable').DataTable();

  // Enable tooltips
  $('[data-bs-toggle="tooltip"]').tooltip();

  // APPROVE
  $(document).on('click','.approveBtn',function(){
    let id=$(this).data('id');

    Swal.fire({
      title: 'Approve Organizer?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Approve'
    }).then((result)=>{
      if(result.isConfirmed){
        $.post('update_user_approval.php',{user_id:id,type:'approve'},function(){
          location.reload();
        });
      }
    });
  });

  // REJECT
  $(document).on('click','.rejectBtn',function(){
    let id=$(this).data('id');

    Swal.fire({
      title: 'Reject Organizer?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Reject'
    }).then((result)=>{
      if(result.isConfirmed){
        $.post('update_user_approval.php',{user_id:id,type:'reject'},function(){
          location.reload();
        });
      }
    });
  });

  // TOGGLE STATUS
  $(document).on('click','.toggleStatusBtn',function(){
    let id=$(this).data('id');

    Swal.fire({
      title: 'Change Status?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Proceed'
    }).then((result)=>{
      if(result.isConfirmed){
        $.post('update_user_status.php',{user_id:id},function(){
          location.reload();
        });
      }
    });
  });

  // DELETE
  $(document).on('click','.deleteUserBtn',function(){
    let id=$(this).data('id');

    Swal.fire({
      title: 'Delete User?',
      text: "This cannot be undone!",
      icon: 'error',
      showCancelButton: true,
      confirmButtonText: 'Delete'
    }).then((result)=>{
      if(result.isConfirmed){
        $.post('delete_user.php',{user_id:id},function(){
          location.reload();
        });
      }
    });
  });

});
</script>