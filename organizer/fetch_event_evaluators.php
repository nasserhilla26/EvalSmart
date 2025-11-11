<?php
include '../includes/auth.php';
include '../includes/db_connect.php';

$event_id = intval($_GET['event_id'] ?? 0);

$query = "
SELECT DISTINCT 
  u.user_id,
  CONCAT(u.first_name, ' ', u.last_name) AS evaluator_name,
  MAX(ea.date_answered) AS submitted_at
FROM evaluation_answers ea
JOIN users u ON ea.user_id = u.user_id
WHERE ea.event_id = '$event_id'
GROUP BY u.user_id
ORDER BY submitted_at DESC
";
$result = mysqli_query($conn, $query);

// 🔹 Check if no evaluators yet
if (mysqli_num_rows($result) == 0) {
  echo "<p class='text-center text-muted'>No evaluators have submitted responses yet for this event.</p>";
  exit;
}

// DataTable-compatible HTML
?>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
        <table id="evaluatorTable" class="table rounded align-middle">
            <thead class="table-primary text-center">
            <tr>
                <th>#</th>
                <th>Evaluator</th>
                <th>Date Submitted</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php 
            $i = 1;
            while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                <td class="text-center"><?= $i++; ?></td>
                <td><?= htmlspecialchars($row['evaluator_name']); ?></td>
                <td class="text-center"><?= date('F j, Y g:i A', strtotime($row['submitted_at'])); ?></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info" onclick="viewResponse(<?= $row['user_id']; ?>, <?= $event_id; ?>)">
                    <i class="fas fa-eye"></i> View Response
                    </button>
                </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>

    </div>
</div>




<!-- ✅ DataTables JS initialization -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  if ($.fn.DataTable.isDataTable('#evaluatorTable')) {
    $('#evaluatorTable').DataTable().destroy(); // Re-initialize safely
  }
  $('#evaluatorTable').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    ordering: true,
    searching: true,
    responsive: true,
    columnDefs: [
      { orderable: false, targets: 3 } // Disable sorting on Action column
    ],
    language: {
      search: "Search Evaluator:",
      lengthMenu: "Show _MENU_ entries",
      zeroRecords: "No matching evaluators found",
      info: "Showing _START_ to _END_ of _TOTAL_ evaluators",
      infoEmpty: "No records available",
      infoFiltered: "(filtered from _MAX_ total records)"
    }
  });
});
</script>

