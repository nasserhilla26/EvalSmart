<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // Organizer only
include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';
include '../includes/db_connect.php';

$organizer_id = $_SESSION['user_id'];
?>

<div class="container-fluid">
  <h1 class="h3 mb-4 text-gray-800">Event Evaluation Results</h1>

  <div class="card shadow mb-4">
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label fw-bold">Select Event:</label>
        <select id="eventSelect" class="form-select">
          <option value="">-- Choose an event --</option>
          <?php
          $events = mysqli_query($conn, "SELECT event_id, event_title FROM events WHERE organizer_id='$organizer_id' ORDER BY event_date DESC");
          while ($e = mysqli_fetch_assoc($events)):
          ?>
            <option value="<?php echo $e['event_id']; ?>"><?php echo htmlspecialchars($e['event_title']); ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div id="resultsSection" style="display:none;">
        <h5 class="fw-bold mt-4 mb-3">Evaluation Summary</h5>
        <div id="summaryData"></div>

        <h5 class="fw-bold mt-4 mb-3">Comments & Suggestions</h5>
        <div id="feedbackSection" class="border rounded p-3 bg-light"></div>

        <div class="text-end mt-4">
          <button id="generateAI" class="btn btn-primary">
            <i class="fas fa-robot"></i> Generate AI Summary & Recommendations
          </button>
        </div>

        <!-- 🧠 AI Summary Output -->
        <div id="aiResult" class="mt-4" style="display:none;">
          <div class="card shadow-lg border-0 rounded-4">
            <div class="card-body p-4">
              <h4 class="card-title text-primary mb-3">
                <i class="fas fa-robot me-2"></i>AI Evaluation Summary
              </h4>
              <div id="aiContent" class="text-dark"></div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
  $('#eventSelect').change(function() {
    const eventId = $(this).val();
    if (!eventId) {
      $('#resultsSection').hide();
      return;
    }

    $.ajax({
      url: 'fetch_results.php',
      type: 'GET',
      data: { event_id: eventId },
      dataType: 'json',
      success: function(data) {
        if (data.success) {
          $('#resultsSection').show();

          // Build summary table
          let summaryHtml = '<table class="table table-bordered">';
          summaryHtml += '<thead><tr><th>Question</th><th>Average Rating / Common Answer</th></tr></thead><tbody>';

          data.summary.forEach(item => {
            summaryHtml += `<tr><td>${item.question}</td><td>${item.value}</td></tr>`;
          });

          summaryHtml += '</tbody></table>';
          $('#summaryData').html(summaryHtml);

          // Comments
          let feedbackHtml = '';
          if (data.comments.length > 0) {
            data.comments.forEach(c => {
              feedbackHtml += `<div class="mb-3"><p class="mb-1"><strong>Comment:</strong> ${c.comment}</p><p><strong>Suggestion:</strong> ${c.suggestion || '—'}</p><hr></div>`;
            });
          } else {
            feedbackHtml = '<p class="text-muted">No comments or suggestions available.</p>';
          }
          $('#feedbackSection').html(feedbackHtml);

          // Attach AI data
          $('#generateAI').data('event', eventId);
        } else {
          Swal.fire({ icon: 'info', title: 'No Data', text: data.message });
        }
      }
    });
  });

  // Generate AI Summary
  $('#generateAI').click(function() {
    const eventId = $(this).data('event');
    if (!eventId) return;

    Swal.fire({
      title: 'Generating AI Summary & Recommendation...',
      text: 'Please wait a few seconds.',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    $.ajax({
      url: 'ai_summary.php',
      type: 'POST',
      data: { event_id: eventId },
      success: function(response) {
        Swal.close();
        $('#aiResult').fadeIn(400);

        // Format AI response (try to detect sections)
        const formattedResponse = response
          .replace(/Summary:/gi, '<h5 class="text-primary mt-1"><i class="fas fa-align-left me-2"></i>Summary</h5>')
          .replace(/Strengths:/gi, '<h5 class="text-success mt-1"><i class="fas fa-check-circle me-2"></i>Strengths</h5><ul>')
          .replace(/Recommendations:/gi, '</ul><h5 class="text-warning mt-1"><i class="fas fa-lightbulb me-2"></i>Recommendations</h5><ul>')
          .replace(/\n/g, '<br>') // keep line breaks
          .concat('</ul>');

        $('#aiContent').html(formattedResponse);
      },
      error: function() {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to generate AI summary.' });
      }
    });
  });
});
</script>
