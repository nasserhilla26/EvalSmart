<?php 
include '../includes/auth.php';
include '../includes/db_connect.php';

include '../includes/header.php';
include '../includes/sidebar.php';
include '../includes/topbar.php';

include '../includes/analytics_helper.php';

// $event_id = intval($_GET['id'] ?? 0);
$event_id = 13;

if(!$event_id){
    die("Invalid Event ID");
}


//Load Event
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

if(mysqli_num_rows($eventQuery) == 0){
    die("Event not found.");
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


?>



<div class="container-fluid">

    <?php if(empty($event['questionnaire_id'])): ?>

    <div class="alert alert-warning">

        <strong>Legacy Event Detected</strong>

        This event is not linked to a questionnaire
        with Scale Management enabled.

        Some analytics may be unavailable.

    </div>

    <?php endif; ?>

    <h1 class="h3 mb-4 text-gray-800">
        Event Evaluation Dashboard
    </h1>

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

    <div class="col-md-4">

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

    <div class="col-md-4">

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

    <div class="col-md-4">

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



<!-- Question Performance Table -->

<div class="card shadow my-4">

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

                        <th width="150">
                            Average
                        </th>

                        <th width="250">
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

                        <td>
                            <?php echo number_format(
                                $question['average'],
                                2
                            ); ?>
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

        </div>

    </div>

</div>

<!-- Question Performance Table -->




<?php
include '../includes/footer.php';
?>



