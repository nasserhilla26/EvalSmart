<?php
// Variables expected:
// $event
// $overallAverage
// $overallInterpretation
// $totalResponses
// $bestCategory
// $lowestCategory
// $categoryResults
// $questionResults
// $topQuestions
// $bottomQuestions
// $aiSummary
?>


<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">

<title>
    Event Evaluation Report
</title>

<style>

body{
    font-family: DejaVu Sans, sans-serif;
    font-size:12px;
    line-height:1.5;
}

h1,h2,h3,h4{
    margin-bottom:5px;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-bottom:15px;
}

table th,
table td{
    border:0.3 solid #383838;
    padding:6px;
}

hr{
    border:0.3 solid #383838;
}

th{
    background:#f2f2f2;
}

.section-title{
    background:#e9ecef;
    padding:8px;
    margin-top:20px;
    font-weight:bold;
}

.summary-table td{
    width:50%;
}

.footer{
    margin-top:30px;
    font-size:10px;
    text-align:center;
    color:#666;
}

@page {
    margin: 80px 40px 80px 40px;
}

.mean-sd{
    text-align: center;
}

</style>

</head>

<body>

<table style="width:100%; border:none; height:100px; margin-top:-35px;">

<tr>

    <td
        style="
            width:100px;
            border:none;
            vertical-align:middle;
        ">

        <img src="<?php echo $logoPath; ?>" width="100">

    </td>

    <td
        style="
            border:none;
            text-align:center;
        ">

        <h2 style="margin:0;">
            PILAR COLLEGE OF ZAMBOANGA CITY, INC.
        </h2>

        <div>
            R.T. Lim Blvd., Zamboanga City
        </div>

    </td>

    <td
        style="
            width:100px;
            border:none;
        ">
    </td>

</tr>
</table>

<div style="text-align:center; margin-top:-40px">
<h2>EvalSMART</h2>

        <h3>
            Event Evaluation Report
        </h3>

        <strong>
            Report ID:
            <?php echo $reportId; ?>
        </strong>
</div>

<hr>

<!-- event information -->
 <div class="section-title">
    Event Information
</div>

<table>

<tr>
    <td><strong>Event Title</strong></td>
    <td><?php echo htmlspecialchars($event['event_title']); ?></td>
</tr>

<tr>
    <td><strong>Description</strong></td>
    <td><?php echo htmlspecialchars($event['event_description']); ?></td>
</tr>

<tr>
    <td><strong>Organizer</strong></td>
    <td><?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']);?></td>
</tr>

<tr>
    <td>
        <strong>Department</strong>
    </td>

    <td>
        <?php echo htmlspecialchars($event['department']); ?>
    </td>
</tr>

<tr>
    <td>
        <strong>Position</strong>
    </td>

    <td>
        <?php echo htmlspecialchars($event['position']); ?>
    </td>
</tr>

<tr>
    <td>
        <strong>Questionnaire</strong>
    </td>

    <td>
        <?php
        echo htmlspecialchars(
            $questionnaire['title']
        );
        ?>
    </td>
</tr>

<tr>
    <td>
        <strong>Evaluation Scale</strong>
    </td>

    <td>
        <?php
        echo htmlspecialchars(
            $scale['scale_name']
        );
        ?>
    </td>
</tr>


<tr>
    <td><strong>Date</strong></td>
    <td><?php echo date('F d, Y', strtotime($event['event_date'])); ?></td>
</tr>

<tr>
    <td><strong>Venue</strong></td>
    <td><?php echo htmlspecialchars($event['event_venue']); ?></td>
</tr>

<tr>
    <td><strong>Total Responses</strong></td>
    <td><?php echo $totalResponses; ?></td>
</tr>

</table>

<!-- Executive Summary Dashboard -->
 <div class="section-title">
    Executive Summary
</div>

<table class="summary-table">

<tr>

<td>
    <strong>Overall Mean</strong><br>
    <?php echo number_format($overallAverage,2); ?>
</td>

<td>
    <strong>Interpretation</strong><br>
    <?php echo $overallInterpretation; ?>
</td>

</tr>

<tr>

<td>
    <strong>Best Category</strong><br>
    <?php echo $bestCategory['category_name']; ?>
</td>

<td>
    <strong>Lowest Category</strong><br>
    <?php echo $lowestCategory['category_name']; ?>
</td>

</tr>

</table>


<!-- Category Performance -->
 <div class="section-title">
    Category Performance
</div>

<table>

<thead>

<tr>
    <th>Category</th>
    <th>Mean</th>
    <th>Std. Dev.</th>
    <th>Interpretation</th>
</tr>

</thead>

<tbody>

<?php foreach($categoryResults as $category): ?>

<tr>

<td>
    <?php echo htmlspecialchars($category['category_name']); ?>
</td>

<td class="mean-sd">
    <?php echo number_format($category['average'],2); ?>
</td>

<td class="mean-sd">
    <?php echo number_format($categorySD[$category['category_name']] ?? 0,2); ?>
</td>

<td>
    <?php echo getInterpretation(
        $conn,
        $scale_id,
        $category['average']
    ); ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>


<!-- Question Performance -->

<div class="section-title">
    Question Insights
</div>

<h4>Top Performing Questions</h4>

<ol>

<?php foreach($topQuestions as $question): ?>

<li>

<?php echo htmlspecialchars($question['question_text']); ?>

(
<?php echo number_format($question['average'],2); ?>
)

</li>

<?php endforeach; ?>

</ol>


<h4>Lowest Performing Questions</h4>

<ol>

<?php foreach($bottomQuestions as $question): ?>

<li>

<?php echo htmlspecialchars($question['question_text']); ?>

(
<?php echo number_format($question['average'],2); ?>
)

</li>

<?php endforeach; ?>

</ol>


<!-- Question Performance -->

<!-- All Questions Table -->

<div class="section-title">
    Question Performance
</div>

<table>

<thead>

<tr>

    <th>Question</th>
    <th>Mean</th>
    <th>Std. Dev.</th>
    <th>Interpretation</th>

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

<td class="mean-sd">
    <?php echo number_format(
        $question['average'],
        2
    ); ?>
</td>

<td class="mean-sd">
    <?php
    echo number_format(
        $questionSD[
            $question['question_id']
        ] ?? 0,
        2
    );
    ?>
</td>

<td>
    <?php
    echo getInterpretation(
        $conn,
        $scale_id,
        $question['average']
    );
    ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

<!-- All Questions Table -->


<!-- AI Summarry  -->

<div class="section-title">
    Summary & Recommendations
</div>

<div>

<?php

echo nl2br(
    htmlspecialchars(
        $aiSummary
    )
);

?>

</div>

<!-- AI Summarry  -->


<!-- Footer -->

<div class="footer">

    <hr>

    <strong>Generated by EvalSMART</strong>

    <br>

    Report ID:
    <?php echo $reportId; ?>

    <br>

    Generated By:
    <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];?>

    <br>

    Generated On:
    <?php echo date('F d, Y h:i A'); ?>

    <br><br>

    This report was automatically generated from
    participant evaluation responses.

</div>
<!-- Footer -->



</body>
</html>


