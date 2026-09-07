<?php 
include '../includes/auth.php';
include '../includes/db_connect.php';

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';

include '../includes/analytics_helper.php';

$user_id = $_SESSION['user_id'];
$roles = $_SESSION['roles'] ?? [];

//block evaluators
if (!in_array(1, $roles) && !in_array(2, $roles)) {

    echo "<script>
            alert('Unauthorized Access');
            window.location='../index.php';
          </script>";
    exit;
}

$event_id = intval($_GET['id'] ?? 0);


if (in_array(1, $roles)) {

    // ADMIN
    $eventQuery = mysqli_query($conn, "
        SELECT
            e.*,
            q.questionnaire_id,
            q.scale_id,
            q.title AS questionnaire_title
        FROM events e
        LEFT JOIN event_questionnaire eq
            ON e.event_id = eq.event_id
        LEFT JOIN questionnaire q
            ON eq.questionnaire_id = q.questionnaire_id
        WHERE e.event_id = '$event_id'
        LIMIT 1
    ");

} else {

    // ORGANIZER
    $eventQuery = mysqli_query($conn, "
        SELECT
            e.*,
            q.questionnaire_id,
            q.scale_id,
            q.title AS questionnaire_title
        FROM events e
        LEFT JOIN event_questionnaire eq
            ON e.event_id = eq.event_id
        LEFT JOIN questionnaire q
            ON eq.questionnaire_id = q.questionnaire_id
        WHERE e.event_id = '$event_id'
        AND e.organizer_id = '$user_id'
        LIMIT 1
    ");

}

if (mysqli_num_rows($eventQuery) == 0) {

    echo "<script>
            alert('You are not authorized to view this event.');
            history.back();
          </script>";

    exit;
}

$event = mysqli_fetch_assoc($eventQuery);


//Load Analytics
$overallAverage = getOverallAverage(
    $conn,
    $event_id
);

// $interpretation = getInterpretation(
//     $conn,
//     $event['scale_id'],
//     $overallAverage
// );

$interpretation = "No Interpretation";

if(
    !empty($event['scale_id'])
    &&
    $overallAverage > 0
){
    $interpretation = getInterpretation(
        $conn,
        $event['scale_id'],
        $overallAverage
    );
}


$categoryResults = getCategoryAverage(
    $conn,
    $event_id
);

// ranking categories
$rankedCategories = $categoryResults;

usort($rankedCategories, function($a, $b) {
    return $b['average'] <=> $a['average'];
});


// Top Performer result
$bestCategory = null;
$lowestCategory = null;

if (!empty($categoryResults)) {

    $bestCategory = $categoryResults[0];
    $lowestCategory = $categoryResults[0];

    foreach ($categoryResults as $cat) {

        if ($cat['average'] > $bestCategory['average']) {
            $bestCategory = $cat;
        }

        if ($cat['average'] < $lowestCategory['average']) {
            $lowestCategory = $cat;
        }
    }
}


$questionResults = getQuestionAverage(
    $conn,
    $event_id
);


// questions ranking
$highestQuestion = null;
$lowestQuestion = null;

if (!empty($questionResults)) {

    $highestQuestion = $questionResults[0];
    $lowestQuestion = $questionResults[0];

    foreach ($questionResults as $question) {

        if ($question['average'] > $highestQuestion['average']) {
            $highestQuestion = $question;
        }

        if ($question['average'] < $lowestQuestion['average']) {
            $lowestQuestion = $question;
        }
    }
}

$rankedQuestions = $questionResults;

usort($rankedQuestions, function ($a, $b) {
    return $b['average'] <=> $a['average'];
});

$topQuestions = array_slice($rankedQuestions, 0, 3);

$bottomQuestions = array_slice(
    array_reverse($rankedQuestions),
    0,
    3
);


// Total Responses

$responseQuery = mysqli_query($conn, "
    SELECT COUNT(DISTINCT user_id) AS total
    FROM evaluation_answers
    WHERE event_id = '$event_id'
");

$responseData = mysqli_fetch_assoc(
    $responseQuery
);

$totalResponses = $responseData['total'];


// Category chart
$categoryLabels = [];
$categoryScores = [];

foreach($categoryResults as $cat){

    $categoryLabels[] = $cat['category_name'];

    $categoryScores[] = $cat['average'];
}


//Frequency Distribution

$frequencyResults =
    getFrequencyDistribution(
        $conn,
        $event_id
    );

// Percentage Distribution
$percentageResults =
    getPercentageDistribution(
        $frequencyResults
    );

$scale_id = getEventScaleId(
    $conn,
    $event_id
);

// scale label for frequency and percentage
$scaleLabels =
    getScaleLabels(
        $conn,
        $scale_id
    );

// Calculate SD
$questionSD =
    getQuestionStandardDeviation(
        $conn,
        $event_id
    );

$categorySD = getCategoryStandardDeviation(
    $conn,
    $event_id
);

// php end tag
?> 

<!-- style for bar chart too large -->
<style>

.chart-container {

    position: relative;

    width: 100%;
    height: 400px;
}

@media (max-width: 992px) {

    .chart-container {

        height: 300px;
    }
}

@media (max-width: 576px) {

    .chart-container {

        height: 250px;
    }
}

</style>


<div class="container-fluid">

    <?php if(empty($event['questionnaire_id'])): ?>

    <div class="alert alert-warning">

        <strong>Legacy Event Detected</strong>

        This event is not linked to a questionnaire
        with Scale Management enabled.

        Some analytics may be unavailable.

    </div>

    <?php endif; ?>
    
    <div class="row">
        <div class="col">
            <h1 class="h3 mb-4 text-gray-800">
            Event Evaluation Dashboard
        </h1>
        </div>

        <div class="col d-flex flex-wrap justify-content-end">
        
        </div>
        
        <div class="col text-end">
            <?php if(in_array(2, $roles)):?>
                <a href="../organizer/view_individual_results.php" class="btn btn-outline-primary mx-3">
                    <i class="fas fa-eye"></i> Evaluator Response
                </a>
            <?php endif; ?>

            <a href="../reports/generate_pdf.php?event_id=<?php echo $event_id; ?>" target="_blank" class="btn btn-danger btn-md">
                <i class="fas fa-file-pdf"></i>
                Download Report
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">

        <div class="card-body">

            <h3 class="fw-bold">
                <?php echo htmlspecialchars(
                    $event['event_title']
                ); ?>
            </h3>

            <p class="text-muted">
                <?php echo htmlspecialchars(
                    $event['event_description']
                ); ?>
            </p>

        </div>

    </div>

    <div class="row">

    <div class="col-md-4 mb-2 ">

        <div class="card border-left-primary shadow h-100">

            <div class="card-body">

                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                    Overall Average
                </div>

                <div class="h2 mb-0 font-weight-bold text-gray-800">

                    <?php echo number_format(
                        $overallAverage,
                        2
                    ); ?>

                </div>

            </div>

        </div>

    </div>

    <div class="col-md-4 mb-2">

        <div class="card border-left-success shadow h-100">

            <div class="card-body">

                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                    Interpretation
                </div>

                <div class="h5 mb-0 font-weight-bold text-gray-800">

                    <?php echo htmlspecialchars(
                        $interpretation
                    ); ?>

                </div>

            </div>

        </div>

    </div>

    <div class="col-md-4 mb-2">

        <div class="card border-left-info shadow h-100">

            <div class="card-body">

                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                    Responses
                </div>

                <div class="h2 mb-0 font-weight-bold text-gray-800">

                    <?php echo $totalResponses; ?>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- Best Performer -->
 <div class="row mt-4">

    <div class="col-md-6">

        <div class="card border-left-success shadow mb-4">

            <div class="card-body">

                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                    Best Performing Category
                </div>

                <?php if($bestCategory): ?>

                    <h5 class="mb-1">
                        <?php echo htmlspecialchars($bestCategory['category_name']); ?>
                    </h5>

                    <div class="h4 font-weight-bold text-success">
                        <?php echo number_format($bestCategory['average'],2); ?>
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <div class="col-md-6">

        <div class="card border-left-danger shadow mb-4">

            <div class="card-body">

                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                    Needs Improvement
                </div>

                <?php if($lowestCategory): ?>

                    <h5 class="mb-1">
                        <?php echo htmlspecialchars($lowestCategory['category_name']); ?>
                    </h5>

                    <div class="h4 font-weight-bold text-danger">
                        <?php echo number_format($lowestCategory['average'],2); ?>
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

<!-- Best Performer -->

<!-- Category Performance Table -->

<div class="card shadow my-4">

    <div class="card-header py-3">

        <h6 class="m-0 font-weight-bold text-primary">
            Category Performance
        </h6>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-light table-striped ">

                <thead class="">

                    <tr>

                        <th>Category</th>

                        <th width="150">
                            Average
                        </th>

                        <th>
                            Std. Dev.
                        </th>

                        <th width="250">
                            Interpretation
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach($categoryResults as $cat): ?>

                    <tr>

                        <td>

                            <?php echo htmlspecialchars(
                                $cat['category_name']
                            ); ?>

                        </td>

                        <td>

                            <?php echo number_format(
                                $cat['average'],
                                2
                            ); ?>

                        </td>
                        
                        <td>
                            <?php
                                echo number_format(
                                    $categorySD[$cat['category_name']] ?? 0,
                                    2
                                );
                            ?>
                        </td>

                        <td>

                            <?php
                            echo getInterpretation(
                                $conn,
                                $event['scale_id'],
                                $cat['average']
                            );
                            ?>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- Category Performance Table -->


<!-- Category Ranking -->

<div class="card shadow mb-4">

    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            Category Ranking
        </h6>
    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-light table-striped">

                <thead>
                    <tr>
                        <th width="10%">Rank</th>
                        <th>Category</th>
                        <th width="20%">Mean</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $rank = 1;

                foreach($rankedCategories as $cat):
                ?>

                    <tr>

                        <td>
                            <span class="badge bg-primary">
                                #<?php echo $rank; ?>
                            </span>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </td>

                        <td>
                            <?php echo number_format($cat['average'], 2); ?>
                        </td>

                    </tr>

                <?php
                    $rank++;
                endforeach;
                ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- Category Ranking -->

<!-- Category Performance Chart -->

<div class="card shadow mb-4">

    <div class="card-header py-3">

        <h6 class="m-0 font-weight-bold text-primary">
            Category Performance Chart
        </h6>

    </div>

    <div class="card-body">

        <div class="chart-container">

            <canvas id="categoryChart"></canvas>

        </div>

    </div>

</div>

<!-- Category Performance Chart -->

<!-- Question Ranking -->

<div class="row">

    <!-- Highest Rate -->
     <div class="col-md-6 mb-4">

        <div class="card border-left-success shadow h-100">

            <div class="card-body">

                <div class="text-xs font-weight-bold text-success text-uppercase mb-2">
                    Highest Rated Question
                </div>

                <div class="h6 mb-2 font-weight-bold">
                    <?php echo htmlspecialchars($highestQuestion['question_text']) ?? "No Data Available"; ?>
                </div>

                <div class="h5 mb-0 text-gray-800">
                    <?php echo number_format($highestQuestion['average'], 2) ?? "No Data Available"; ?>
                </div>

            </div>

        </div>

    </div>

    <!-- Lowest Rate -->

    <div class="col-md-6 mb-4">

        <div class="card border-left-danger shadow h-100">

            <div class="card-body">

                <div class="text-xs font-weight-bold text-danger text-uppercase mb-2">
                    Lowest Rated Question
                </div>

                <div class="h6 mb-2 font-weight-bold">
                    <?php echo htmlspecialchars($lowestQuestion['question_text']); ?>
                </div>

                <div class="h5 mb-0 text-gray-800">
                    <?php echo number_format($lowestQuestion['average'], 2); ?>
                </div>

            </div>

        </div>

    </div>
<!-- End Div row -->
</div>

<div class="row">

<!-- Top 3 Performing -->
    <div class="col-lg-6 mb-4">

        <div class="card shadow">

            <div class="card-header bg-success text-white">
                Top Performing Questions
            </div>

            <div class="card-body">

                <ol class="mb-0">

                    <?php foreach ($topQuestions as $question): ?>

                        <li class="mb-2">

                            <?php echo htmlspecialchars($question['question_text']); ?>

                            <br>

                            <small class="text-dark">
                                Mean:
                                <?php echo number_format($question['average'], 2); ?>
                            </small>

                        </li>

                    <?php endforeach; ?>

                </ol>

            </div>

        </div>

    </div>

    <!-- Lowest 3 Performing -->
     <div class="col-lg-6">

    <div class="card shadow">

        <div class="card-header bg-danger text-white">
            Lowest Performing Questions
        </div>

        <div class="card-body">

            <ol class="mb-0">

                <?php foreach ($bottomQuestions as $question): ?>

                    <li class="mb-2">

                        <?php echo htmlspecialchars($question['question_text']); ?>

                        <br>

                        <small class="text-dark">
                            Mean:
                            <?php echo number_format($question['average'], 2); ?>
                        </small>

                    </li>

                <?php endforeach; ?>

            </ol>

        </div>

    </div>

</div>

    
<!-- End Div -->
</div>

<!-- Question Ranking -->

<!-- Question Performance Table -->

<div class="card shadow my-2">

    <div class="card-header py-3">

        <h6 class="m-0 font-weight-bold text-primary">
            Question Performance
        </h6>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-light table-bordered table-striped">

                <thead>

                    <tr>

                        <th>Question</th>

                        <th width="100" class="text-center">
                            Average
                        </th>

                        <th width="100" class="text-center">
                            Std. Dev.
                        </th>

                        <th width="250" class="text-center">
                            Interpretation
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach($questionResults as $question): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars(
                                $question['question_text']
                            ); ?>
                        </td>

                        <td class="text-center">
                            <?php echo number_format(
                                $question['average'],
                                2
                            ); ?>
                        </td>

                        <td class="text-center">
                        <!-- SD -->
                         <?php
                            echo number_format(
                                $questionSD[$question['question_id']] ?? 0,
                                2
                            );
                        ?>
                        </td>

                        <td>
                            <?php
                            echo getInterpretation(
                                $conn,
                                $event['scale_id'],
                                $question['average']
                            );
                            ?>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>
            <hr>
            <div class="alert alert-info">

                <strong>Note:</strong>

                Standard deviation measures the variability of responses.

                Lower values indicate greater agreement among respondents,
                while higher values indicate more varied opinions.

            </div>

        </div>

    </div>

</div>

<!-- Question Performance Table -->


<!-- Frequency Distribution -->
<div class="card shadow mb-2 mt-4">

    <div class="card-header py-3 d-flex justify-content-between align-items-center">
       
        <h6 class="m-0 font-weight-bold text-primary">
            Frequency Distribution
        </h6>

        <button
            class="btn btn-sm btn-primary"
            type="button"
            data-toggle="collapse"
            data-target="#frequencyCollapse"
            aria-expanded="false"
            aria-controls="frequencyCollapse">

            <i class="fas fa-chevron-down"></i>
            Show / Hide

        </button>

    </div>

    <div class="collapse" id="frequencyCollapse">

        <div class="card-body">

            <?php foreach($frequencyResults as $freq): ?>

                <div class="mb-4">

                    <strong>
                        <?php echo htmlspecialchars($freq['question_text']); ?>
                    </strong>

                    <table class="table table-bordered table-sm mt-2">

                        <thead class="thead-light">

                            <tr>

                                <?php
                                krsort($freq['frequency']);

                                foreach($freq['frequency'] as $scale => $count):
                                ?>

                                    <th class="text-center">
                                        <?php echo htmlspecialchars(
                                            $scaleLabels[$scale] . " ({$scale})" ?? $scale
                                        ); ?>
                                    </th>

                                <?php endforeach; ?>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <?php
                                foreach($freq['frequency'] as $scale => $count):
                                ?>

                                    <td class="text-center">
                                        <?php echo $count; ?>
                                    </td>

                                <?php endforeach; ?>

                            </tr>

                        </tbody>

                    </table>

                </div>

            <?php endforeach; ?>


        </div>

    </div>

</div>
    

<!-- Frequency Distribution -->

<!-- Percentage Distribution -->

<div class="card shadow mb-4">

    <div class="card-header py-3 d-flex justify-content-between align-items-center">

        <h6 class="m-0 font-weight-bold text-primary">
            <!-- <i class="fas fa-percent"></i> -->
            Percentage Distribution
        </h6>


        <button
            class="btn btn-sm btn-primary"
            type="button"
            data-toggle="collapse"
            data-target="#percentageCollapse"
            aria-expanded="false"
            aria-controls="percentageCollapse">

            <i class="fas fa-chevron-down"></i>
            Show / Hide

        </button>

    </div>

    <div class="collapse" id="percentageCollapse">

        <div class="card-body">

            <?php foreach($percentageResults as $percent): ?>

                <div class="mb-4">

                    <strong>
                        <?php echo htmlspecialchars(
                            $percent['question_text']
                        ); ?>
                    </strong>

                    <table class="table table-bordered table-sm mt-2">

                        <thead class="thead-light">

                            <tr>

                                <?php
                                krsort(
                                    $percent['percentage']
                                );

                                foreach(
                                    $percent['percentage']
                                    as $scale => $value
                                ):
                                ?>

                                    <th class="text-center">
                                        <?php echo htmlspecialchars(
                                            $scaleLabels[$scale] . " ({$scale})" ?? $scale
                                        ); ?>
                                    </th>

                                <?php endforeach; ?>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <?php foreach(
                                    $percent['percentage']
                                    as $value
                                ): ?>

                                    <td class="text-center">

                                        <?php
                                        echo number_format(
                                            $value,
                                            2
                                        );
                                        ?>%

                                    </td>

                                <?php endforeach; ?>

                            </tr>

                        </tbody>

                    </table>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</div>

<!-- Percentage Distribution -->

<div class="text-end my-4">
    <button id="generateAI" class="btn btn-primary" data-event-id="<?= $event_id; ?>">
    <i class="fas fa-robot"></i> Generate AI Summary & Recommendations
    </button>
</div>

<!-- AI Summary & Recommendation Output-->

<div id="aiResult" class="my-4" style="display:none;">
    <div class="card shadow-lg border-0 rounded-4">
    <div class="card-body p-4">
        <h4 class="card-title text-primary mb-3">
        <i class="fas fa-robot me-2"></i>AI Evaluation Summary
        </h4>
        <div id="aiContent" class="text-dark"></div>
    </div>
    </div>
</div>

<!-- AI Summary & Recommendation -->


<?php
include '../includes/footer.php';
?>



<script>

const ctx = document.getElementById('categoryChart');

new Chart(ctx, {

    type: 'bar',

    data: {

        labels:
            <?php echo json_encode(
                $categoryLabels
            ); ?>,

        datasets: [{

            label: 'Average Score',

            data:
                <?php echo json_encode(
                    $categoryScores
                ); ?>

        }]
    },

    options: {

        responsive: true,
        maintainAspectRatio: false,

        scales: {

            y: {

                beginAtZero: true
            }
        }
    }
});


// Generate or View Existing AI Summary
  $('#generateAI').click(function() {
    var eventId = $('#generateAI').data('event-id');
    
    
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


function formatAIReport(content)
{
    const sections = {

        'Executive Summary':
            '<h4 class="text-primary "><i class="fas fa-file-alt"></i> Executive Summary</h4>',

        'Question-Level Insights':
            '<h4 class="text-success mt-4"><i class="fas fa-chart-bar"></i> Question-Level Insights</h4>',

        'Strengths:':
            '<h4 class="text-success mt-4"><i class="fas fa-chart-bar"></i> Strengths</h4>',

        'Weaknesses:':
            '<h4 class="text-success mt-4"><i class="fas fa-chart-bar"></i> Weaknesses</h4>',

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

</script>

