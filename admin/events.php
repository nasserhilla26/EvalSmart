<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(1); // Admin only
include '../includes/db_connect.php';
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';



// $result = mysqli_query($conn, "
//   SELECT e.*, u.last_name AS organizer_name 
//   FROM events e
//   JOIN users u ON e.organizer_id = u.user_id
//   ORDER BY e.event_date DESC
// ");

$result = mysqli_query($conn, "
  SELECT 
    e.*, 
    u.last_name AS organizer_name,

    -- count responses
    (SELECT COUNT(DISTINCT ea.user_id) 
    FROM evaluation_answers ea 
    WHERE ea.event_id = e.event_id) AS total_responses,

    -- check if summary exists
    (SELECT COUNT(*) 
     FROM ai_summary s 
     WHERE s.event_id = e.event_id) AS has_summary

  FROM events e
  JOIN users u ON e.organizer_id = u.user_id
  ORDER BY e.event_date DESC
");

?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">All Events</h1>

  <div class="card shadow mb-4">
    <div class="card-body table-responsive">
      <table id="eventsTable" class="table table-bordered table-striped">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Event Title</th>
            <th>Date</th>
            <th>Venue</th>
            <th>Organizer</th>
            <th>Status</th>
            <th>Responses</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($row['event_title']); ?></td>
              <td><?php echo htmlspecialchars(date('F j, Y', strtotime($row['event_date']))); ?></td>
              <td><?php echo htmlspecialchars($row['event_venue']); ?></td>
              <td><?php echo htmlspecialchars($row['organizer_name']); ?></td>
              <td><span class="badge bg-<?php echo ($row['status'] == 'Completed') ? 'success' : 'secondary'; ?>">
                <?php echo $row['status']; ?></span></td>
              <td><span class="badge bg-info"><?= $row['total_responses'] ?> responses</span></td>
              <td>
                <?php
                  $isCompleted = ($row['status'] == 'Completed');
                  $hasResponses = ($row['total_responses'] > 0);
                  $hasSummary = ($row['has_summary'] > 0);
                  ?>

                  <!-- AI BUTTON -->
                  <button 
                    class="btn btn-sm btn-info aiBtn"
                    data-id="<?= $row['event_id'] ?>"
                    title="<?= (!$isCompleted || !$hasResponses) 
                              ? 'Event must be completed with responses' 
                              : ($hasSummary ? 'Regenerate AI Summary' : 'Generate AI Summary') ?>"
                    <?= (!$isCompleted || !$hasResponses) ? 'disabled' : '' ?>
                  >
                    <i class="fas fa-brain"></i>
                  </button>

                  <!-- VIEW BUTTON -->
                  <?php if ($hasSummary): ?>
                  <button 
                    class="btn btn-sm btn-secondary viewSummaryBtn"
                    data-id="<?= $row['event_id'] ?>"
                    title="View AI Summary">
                    <i class="fas fa-eye"></i>
                  </button>
                  <?php endif; ?>
                  
                  <?php if($isCompleted): ?>
                  <a href="../shared/event_result.php?id=<?php echo $row['event_id']; ?>" 
                    class="btn btn-sm btn-success"
                    title="View Evaluation Results">
                      <i class="fas fa-chart-bar"></i>
                  </a>
                  <?php endif;?>
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
  $('#eventsTable').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    order: [[2, 'desc']]
  });
});


// GENERATE AI SUMMARY
$(document).on('click','.aiBtn', function(){

  let event_id = $(this).data('id');

  Swal.fire({
    title: 'Generate AI Summary?',
    text: "This will analyze all evaluation responses.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Generate'
  }).then((result)=>{
    if(result.isConfirmed){

      Swal.fire({
        title: 'Processing...',
        text: 'Generating AI summary...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      $.post('../ai/ai_summary.php', {event_id:event_id}, function(res){

        Swal.fire({
          icon: 'success',
          title: 'Generated',
          text: 'AI Summary created successfully'
        });

      }).fail(function(){
        Swal.fire('Error','Failed to generate summary','error');
      });

    }
  });
});


function formatAISummary(text) {

    let html = text;

    // Convert headings
    html = html.replace(/\*\*Summary:?\*\*/gi,
        '<h5 class="text-primary mb-2"><i class="fas fa-file-alt"></i> Executive Summary</h5>');

    html = html.replace(/\*\*Key Strengths:?\*\*/gi,
        '<h5 class="text-success mt-3 mb-2"><i class="fas fa-check-circle"></i> Key Strengths</h5>');

    html = html.replace(/\*\*Key Weaknesses:?\*\*/gi,
        '<h5 class="text-warning mt-3 mb-2"><i class="fas fa-exclamation-triangle"></i> Areas for Improvement</h5>');

    html = html.replace(/\*\*Recommendations for Improvement:?\*\*/gi,
        '<h5 class="text-info mt-3 mb-2"><i class="fas fa-lightbulb"></i> Recommendations</h5>');

    // Convert bullet points
    html = html.replace(/\* /g, '• ');

    // Preserve line breaks
    html = html.replace(/\n/g, '<br>');

    return html;
}


// VIEW AI SUMMARY
$(document).on('click','.viewSummaryBtn', function(){

  let event_id = $(this).data('id');

  $.get('../ai/fetch_ai_summary.php', {event_id:event_id}, function(res){

    let data = JSON.parse(res);

    if(!data.success){
      Swal.fire('No Summary','Generate summary first','info');
      return;
    }

    Swal.fire({
    title: '<i class="fas fa-brain text-primary"></i> AI Event Evaluation Report',
    width: 800,
    html: `
        <div class="text-start">

            <div class="alert alert-light">
                ${formatAISummary(data.summary)}
            </div>

            <div class="text-end">
                <small class="text-muted">
                    <i class="fas fa-clock"></i>
                    Generated: ${data.generated_on}
                </small>
            </div>

        </div>
    `,
    confirmButtonText: 'Close'
}); 

  });

});


</script>