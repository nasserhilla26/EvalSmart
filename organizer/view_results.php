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

<style>
  .ai-report {

    line-height: 1.5;

    font-size: 18px;

    color: #495057;
}
</style>

<div class="container-fluid">
  
  
    <div class="row mb-2 ">

      <div class="col">
        <h1 class="h3 mb-2 text-gray-800">Event Evaluation Results</h1>
      </div>

      <div class="col d-flex flex-wrap justify-content-end">
        <a href="view_individual_results.php" class="btn btn-primary mb-2 mx-2">
          <i class="fas fa-eye"></i> Evaluator Response
        </a>
      
      <!-- Download report btn -->
        <form action="download_report.php" method="POST" target="_blank">
          <input type="hidden" name="event_id" id="report_event_id">
          <button type="submit" class="btn btn-outline-danger">
            <i class="fas fa-file-download"></i> Download Report (PDF)
          </button>
        </form>
      </div>

      <div>
        <p><i><strong>Note: </strong>Only the completed events can generate results and report.</i></p>
      </div>

    </div>
    
  

  <div class="card shadow mb-4">
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label fw-bold">Select Event:</label>
        <select id="eventSelect" class="form-select">
          <option value="">-- Choose an event --</option>
          <?php

          

          $events = mysqli_query($conn, "SELECT event_id, event_title FROM events WHERE organizer_id='$organizer_id' AND status='Completed' ORDER BY event_date DESC");
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

        <!--  AI Summary Output -->
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

<!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->


<script>

$(document).ready(function() {
  // When selecting a new event
  $('#eventSelect').change(function() {
    const eventId = $(this).val();

    $('#report_event_id').val(eventId);

    // Hide previous AI summary to avoid confusion
    $('#aiResult').hide().find('#aiContent').empty();

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
          summaryHtml += '<thead><tr><th>Question</th><th>Average Rating</th></tr></thead><tbody>';

          $('#summaryData').html(summaryHtml);

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

          // Attach event to Generate button
          $('#generateAI').data('event', eventId);
        } else {
          Swal.fire({ icon: 'info', title: 'No Data', text: data.message });
        }
      }
    });
  });

  // Generate or View Existing AI Summary
  $('#generateAI').click(function() {
    const eventId = $(this).data('event');
    
    
    if (!eventId) return;

    $.ajax({
      url: '../ai/fetch_ai_summary.php',
      type: 'GET',
      data: { event_id: eventId },
      dataType: 'json',
      success: function(ai) {
        if (ai.success && ai.summary) {
          Swal.fire({
            title: 'AI Summary Already Exists',
            text: 'A summary for this event already exists. Do you want to regenerate it?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Regenerate',
            cancelButtonText: 'View Existing',
            reverseButtons: true
          }).then((result) => {
            if (result.isConfirmed) {
              generateAISummary(eventId, true);
            } else {
              showAISummary(ai.summary, ai.generated_on);
            }
          });
        } else {
          generateAISummary(eventId, false);
        }
      },
      error: function() {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to check AI summary.' });
      }
    });
  });

  // Helper: generate AI summary via Ollama
  function generateAISummary(eventId, regenerate = false) {
    Swal.fire({
      title: regenerate ? 'Regenerating AI Summary...' : 'Generating AI Summary & Recommendation...',
      text: 'Please wait a few seconds.',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    $.ajax({
      url: '../ai/ai_summary.php',
      type: 'POST',
      data: { event_id: eventId },
      success: function(response) {
        Swal.close();
        showAISummary(response, new Date().toLocaleString());
        
      },
      error: function() {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to generate AI summary.' });
      }
    });
  }

  // Helper: render AI summary card
  // function showAISummary(content, dateGenerated) {
  //   $('#aiResult').fadeIn(400);
  //   const formatted = content
  //     .replace(/Summary:/gi, '<h5 class="text-primary mt-1"><i class="fas fa-align-left me-2"></i>Summary</h5>')
  //     .replace(/Strengths:/gi, '<h5 class="text-success mt-1"><i class="fas fa-check-circle me-2"></i>Strengths</h5><ul>')
  //     .replace(/Weaknesses:/gi, '<h5 class="text-success mt-1"><i class="fas fa-check-circle me-2"></i>Weeknesses</h5><ul>')
  //     .replace(/Recommendations for Improvement:/gi, '</ul><h5 class="text-warning mt-1"><i class="fas fa-lightbulb me-2"></i>Recommendations</h5><ul>')
  //     .replace(/\n/g, '<br>')
  //     .concat('</ul>');

  //   $('#aiContent').html(`
  //     <div class="text-end text-muted small mb-2">
  //       <i class="fas fa-clock me-1"></i>Generated on: ${dateGenerated}
  //     </div>
  //     ${formatted}
  //   `);
  // }

function formatAIReport(content)
{
    const sections = {

        'Executive Summary':
            '<h4 class="text-primary "><i class="fas fa-file-alt"></i> Executive Summary</h4>',

        'Question-Level Insights':
            '<h4 class="text-success mt-4"><i class="fas fa-chart-bar"></i> Question-Level Insights</h4>',

        'Positive Themes':
            '<h4 class="text-info mt-4"><i class="fas fa-thumbs-up"></i> Positive Themes</h4>',

        'Improvement Themes':
            '<h4 class="text-warning mt-4"><i class="fas fa-tools"></i> Improvement Themes</h4>',

        'Recommendations':
            '<h4 class="text-danger mt-4"><i class="fas fa-lightbulb"></i> Recommendations</h4>',

        'Overall Assessment':
            '<h4 class="text-dark mt-4"><i class="fas fa-check-circle"></i> Overall Assessment</h4>'
    };

    Object.keys(sections).forEach(key => {

        content = content.replace(
            new RegExp(key, 'gi'),
            sections[key]
        );

    });

    content = content.replace(
    /Event Evaluation Report:/gi,
    '<h4 class="text-primary fw-bold"></i>Event Evaluation Report</h3>'
    );

    return content.replace(/\n/g, '<br>');
    
}

function showAISummary(content, dateGenerated)
{
    $('#aiResult').fadeIn(400);

    const formattedContent =
        formatAIReport(content);

    $('#aiContent').html(`
    <div class="ai-report">

        <div class="text-end text-muted small mb-3">
            <i class="fas fa-clock me-1"></i>
            Generated on: ${dateGenerated}
        </div>

        ${formattedContent}

    </div>
`);
}




});










  // Generate AI Summary
  // $('#generateAI').click(function() {
  //   const eventId = $(this).data('event');
  //   if (!eventId) return;

  //   Swal.fire({
  //     title: 'Generating AI Summary & Recommendation...',
  //     text: 'Please wait a few seconds.',
  //     allowOutsideClick: false,
  //     didOpen: () => Swal.showLoading()
  //   });

  //   $.ajax({
  //     url: 'ai_summary.php',
  //     type: 'POST',
  //     data: { event_id: eventId },
  //     success: function(response) {
  //       Swal.close();
  //       $('#aiResult').fadeIn(400);

  //       // Format AI response (try to detect sections)
  //       const formattedResponse = response
  //         .replace(/Summary:/gi, '<h5 class="text-primary mt-1"><i class="fas fa-align-left me-2"></i>Summary</h5>')
  //         .replace(/Strengths:/gi, '<h5 class="text-success mt-1"><i class="fas fa-check-circle me-2"></i>Strengths</h5><ul>')
  //         .replace(/Recommendations:/gi, '</ul><h5 class="text-warning mt-1"><i class="fas fa-lightbulb me-2"></i>Recommendations</h5><ul>')
  //         .replace(/\n/g, '<br>') // keep line breaks
  //         .concat('</ul>');

  //       $('#aiContent').html(formattedResponse);
  //     },
  //     error: function() {
  //       Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to generate AI summary.' });
  //     }
  //   });

  //   // After building summary and comments
  //   $.ajax({
  //     url: './fetch_ai_summary.php',
  //     type: 'GET',
  //     data: { event_id: eventId },
  //     dataType: 'json',
  //     success: function(ai) {
  //       if (ai.success && ai.summary) {
  //         $('#aiResult').fadeIn(400);
  //         $('#aiContent').html(ai.summary.replace(/\n/g, '<br>'));
  //       } else {
  //         $('#aiResult').hide();
  //       }
  //     }
  //   });



  // });









</script>
