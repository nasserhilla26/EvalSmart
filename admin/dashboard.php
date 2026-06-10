<?php

include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1); // only admin

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';


// =======================
// FETCH DATA
// =======================

// Total Users
$total_users = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users"))[0];

// Total Events
$total_events = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM events"))[0];

// Total Evaluations
$total_evaluations = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM evaluation_answers"))[0];

// Pending Organizers
$pending_organizers = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role_status='Pending'"))[0];

// Pending list (limit 5)
$pending_list = mysqli_query($conn, "
  SELECT user_id, first_name, last_name, email 
  FROM users 
  WHERE role_status='Pending' 
  ORDER BY user_id DESC 
  LIMIT 5
");


// Pack data
$data = [
  'total_users' => $total_users,
  'total_events' => $total_events,
  'total_evaluations' => $total_evaluations,
  'pending_organizers' => $pending_organizers,
  'pending_list' => $pending_list
];
?>




<!-- Begin Page Content -->
<div class="container-fluid">

  <!-- Page Heading -->
  <h1 class="h3 mb-4 text-gray-800">
    Dashboard
  </h1>


    <!-- ===================== ADMIN SECTION ===================== -->
    <div class="row">
      <div class="col-lg mb-4">
        <div class="card shadow h-100">
          <div class="card-body">
            <h4 class="text-primary">Welcome, <?php echo $_SESSION['full_name']; ?>!</h4>
            <p class="text-muted">You are logged in as <strong>Administrator</strong>.</p>
            <p>As an Admin, you can manage all users, events, and view global evaluation results.</p>
          </div>
        </div>
      </div>
    </div>

    <?php include '../includes/dashboard/stats_cards.php'; ?>


    <div class="row">
      <div class="col-lg-6">
       <?php include '../includes/dashboard/pending_panel.php'; ?>
      </div>

      <div class="col-lg-6 mb-4">
        <div class="card shadow h-100 py-2">
          <div class="card-body">
            <h5 class="text-success">Quick Actions</h5>
            <ul class="list-group">
              <li class="list-group-item"><a href="review_questionnaires.php"><i class="fas fa-pen"></i> Review Questionnaires</a></li>
              <li class="list-group-item"><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
              <li class="list-group-item"><a href="events.php"><i class="fas fa-calendar-alt"></i> Manage Events</a></li>
              <li class="list-group-item"><a href="reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    

    

    <div class="row">
    


  </div>



</div>
<!-- /.container-fluid -->

<?php include '../includes/footer.php'; ?>

<script>

$(document).on('click','.approveBtn',function(){
  let id=$(this).data('id');

  Swal.fire({
    title:'Approve this user?',
    icon:'question',
    showCancelButton:true
  }).then(res=>{
    if(res.isConfirmed){
      $.post('update_user_approval.php',{user_id:id,type:'approve'},()=>location.reload());
    }
  });
});

$(document).on('click','.rejectBtn',function(){
  let id=$(this).data('id');

  Swal.fire({
    title:'Reject this user?',
    icon:'warning',
    showCancelButton:true
  }).then(res=>{
    if(res.isConfirmed){
      $.post('update_user_approval.php',{user_id:id,type:'reject'},()=>location.reload());
    }
  });
});






</script>