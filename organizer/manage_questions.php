<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

$organizer_id = $_SESSION['user_id'];
$query = "
  SELECT q.*, e.event_title 
  FROM event_questionnaire q 
  JOIN events e ON q.event_id = e.event_id 
  WHERE e.organizer_id='$organizer_id'
  ORDER BY q.event_id DESC, q.question_id ASC";
$result = mysqli_query($conn, $query);
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Manage Evaluation Questions</h1>

  <div class="card shadow mb-4">
    <div class="card-body table-responsive">
      <table id="questionTable" class="table table-bordered table-striped">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Event</th>
            <th>Question</th>
            <th>Type</th>
            <th>Options</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $i = 1;
          while ($row = mysqli_fetch_assoc($result)): 
            $opts = $row['options'] ? implode(", ", json_decode($row['options'], true)) : '-';
          ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($row['event_title']); ?></td>
              <td><?php echo htmlspecialchars($row['question_text']); ?></td>
              <td><?php echo ucfirst($row['question_type']); ?></td>
              <td><?php echo htmlspecialchars($opts); ?></td>
              <td>
                <a href="edit_question.php?id=<?php echo $row['question_id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                <a href="delete_question.php?id=<?php echo $row['question_id']; ?>" class="btn btn-danger btn-sm deleteBtn"><i class="fas fa-trash"></i></a>
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
$(document).ready(function() {
  $('#questionTable').DataTable();

  // Delete confirmation
  $('.deleteBtn').on('click', function(e) {
    e.preventDefault();
    const url = $(this).attr('href');
    Swal.fire({
      title: 'Delete this question?',
      text: "This cannot be undone.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = url;
      }
    });
  });
});
</script>
