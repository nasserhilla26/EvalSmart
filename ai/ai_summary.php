<?php
include '../includes/auth.php';
include '../includes/role_check.php';
include '../includes/notification_service.php';

if (!in_array($_SESSION['active_role'], [1,2])) {
    die("Unauthorized access");
}

include '../includes/db_connect.php';

set_time_limit(180); // for generating AI output

include '../includes/openai_config.php'; // Ollama connection
include '../includes/analytics_helper.php';


header('Content-Type: text/plain; charset=UTF-8');

$event_id = intval($_POST['event_id']);
if (!$event_id) {
    echo "Error: Missing event ID.";
    exit;
}

// event details
// $eventQuery = mysqli_query($conn,"
//     SELECT event_title,event_description
//     FROM events
//     WHERE event_id='$event_id'
// ");

// $event = mysqli_fetch_assoc($eventQuery);

$organizer_id = $_SESSION['user_id'];

$eventQuery = mysqli_query($conn,"
    SELECT
        e.event_title,
        e.event_description,
        e.event_date,
        e.event_venue,
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

$event = mysqli_fetch_assoc($eventQuery);

$event_title = $event['event_title'];

if (empty($event['scale_id'])) {

    die(
        "No evaluation scale assigned to this questionnaire."
    );
}


// Collect all responses
$query = mysqli_query($conn, "
  SELECT answer_text, comments, suggestions 
  FROM evaluation_answers 
  WHERE event_id = '$event_id'
");

$textData = '';
while ($row = mysqli_fetch_assoc($query)) {
    if (!empty($row['comments'])) $textData .= "Comment: {$row['comments']}\n";
    if (!empty($row['suggestions'])) $textData .= "Suggestion: {$row['suggestions']}\n";
}


// Statistics result (Category Mean, Question MEan, Overall mean)

$overallAverage = getOverallAverage(
    $conn,
    $event_id
);

$categoryResults = getCategoryAverage(
    $conn,
    $event_id
);

$questionResults = getQuestionAverage(
    $conn,
    $event_id
);

$scale_id = $event['scale_id'];

$overallInterpretation = getInterpretation(
    $conn,
    $scale_id,
    $overallAverage
);


// Category Summary Block
$categoryText = "";

foreach ($categoryResults as $cat) {

    $categoryInterpretation =
        getInterpretation(
            $conn,
            $scale_id,
            $cat['average']
        );

    $categoryText .=
        "Category: {$cat['category_name']}\n" .
        "Mean: " . number_format($cat['average'], 2) . "\n" .
        "Interpretation: {$categoryInterpretation}\n\n";
}

// Question Summary 
$questionText = "";

foreach ($questionResults as $q) {

    $questionInterpretation =
        getInterpretation(
            $conn,
            $scale_id,
            $q['average']
        );

    $questionText .=
        "Question: {$q['question_text']}\n" .
        "Mean: " . number_format($q['average'], 2) . "\n" .
        "Interpretation: {$questionInterpretation}\n\n";
}

// Get Top and Bottom result
$sortedQuestions = $questionResults;

usort($sortedQuestions, function($a, $b) {
    return $b['average'] <=> $a['average'];
});

$topQuestions = array_slice($sortedQuestions, 0, 3);

$lowestQuestions = array_slice(
    array_reverse($sortedQuestions),
    0,
    3
);

$topQuestionsText = "";

foreach ($topQuestions as $q) {

    $topQuestionsText .=
        "- {$q['question_text']} (" .
        number_format($q['average'], 2) .
        ")\n";
}

$lowestQuestionsText = "";

foreach ($lowestQuestions as $q) {

    $lowestQuestionsText .=
        "- {$q['question_text']} (" .
        number_format($q['average'], 2) .
        ")\n";
}

//Get total Responses
$responseQuery = mysqli_query($conn, "
    SELECT COUNT(DISTINCT user_id) AS total
    FROM evaluation_answers
    WHERE event_id = '$event_id'
");

$responseData = mysqli_fetch_assoc(
    $responseQuery
);

$totalResponses = $responseData['total'];

//Sample size for open-ended, comments and suggestions. random selection
$qualitativeData =
    getQualitativeDataset(
        $conn,
        $event_id
    );

$sampleSize =
    getQualitativeSampleSize(
        $totalResponses
    );

shuffle($qualitativeData['open_ended']);
shuffle($qualitativeData['comments']);
shuffle($qualitativeData['suggestions']);

$openEnded =
    array_slice(
        $qualitativeData['open_ended'],
        0,
        $sampleSize
    );

$comments =
    array_slice(
        $qualitativeData['comments'],
        0,
        $sampleSize
    );

$suggestions =
    array_slice(
        $qualitativeData['suggestions'],
        0,
        $sampleSize
    );


// Open-ended Answers

$qualitativeText = '';

$qualitativeText .=
    "OPEN-ENDED RESPONSES\n\n";

foreach ($openEnded as $item)
{
    $qualitativeText .=
        "Question: "
        . $item['question']
        . "\n";

    $qualitativeText .=
        "Response: "
        . $item['answer']
        . "\n\n";
}

// Evaluators Comment
$qualitativeText .=
    "\nCOMMENTS\n\n";

foreach ($comments as $comment)
{
    $qualitativeText .=
        $comment . "\n";
}

//evaluators Suggestion
$qualitativeText .=
    "\nSUGGESTIONS\n\n";

foreach ($suggestions as $suggestion)
{
    $qualitativeText .=
        $suggestion . "\n";
}

// set status = none if no available comments and suggestions
$qualitativeStatus = 'NONE';

if (
    !empty(trim($qualitativeText))
    && trim($qualitativeText) !== 'NOT AVAILABLE'
) {
    $qualitativeStatus = 'AVAILABLE';
}

// Prepare AI prompt

$prompt = "

You are an Educational Quality Assurance Analyst.

Analyze the evaluation results and generate a concise professional assessment report.

==================================================
EVENT INFORMATION
=================

Event Title:
{$event['event_title']}

==================================================
OVERALL RESULTS
===============

Overall Mean:
{$overallAverage}

Overall Interpretation:
{$overallInterpretation}

==================================================
TOP RATED QUESTIONS
===================

{$topQuestionsText}

==================================================
LOWEST RATED QUESTIONS
======================

{$lowestQuestionsText}

==================================================
QUALITATIVE STATUS
=====================



If {$qualitativeStatus} is AVAILABLE:

Generate Positive Themes and Improvement Themes.

If {$qualitativeStatus} is NOT AVAILABLE:

Write:

Insufficient qualitative data was available for thematic analysis.

==================================================
QUALITATIVE RESPONSES
=====================

{$qualitativeText}

==================================================
REPORT REQUIREMENTS
===================

Generate ONLY the following sections:

Executive Summary

Question-Level Insights

Positive Themes

Improvement Themes

Recommendations

Overall Assessment

==================================================
ANALYSIS RULES
==============

1. Use only the supplied evaluation data.

2. Use TOP RATED QUESTIONS when discussing strengths.

3. Use LOWEST RATED QUESTIONS when discussing weaknesses and improvement opportunities.

4. Use QUALITATIVE RESPONSES when identifying themes and recommendations.

5. Recommendations must be supported by:

   1. Lowest Rated Questions
   2. Open-Ended Responses
   3. Comments
   4. Suggestions

6. Do not generate recommendations unrelated to the supplied data.

7. Do not invent participant opinions, concerns, experiences, or suggestions.

8. Do not infer specific activities, demonstrations, technologies, topics, workshops, software, equipment, or learning activities unless explicitly stated in participant responses.

9. If no qualitative responses are available, write:

Insufficient qualitative data was available for thematic analysis.

for both:

Positive Themes

Improvement Themes

==================================================
OUTPUT FORMAT
=============

Executive Summary

Write one concise paragraph summarizing the overall evaluation.

Question-Level Insights

Discuss the strongest and weakest evaluation criteria based on the supplied question results.

Positive Themes

Identify recurring positive themes from participant responses.

Improvement Themes

Identify recurring concerns or suggestions from participant responses.

Recommendations

Provide 3 or more actionable recommendations directly supported by:

1. Lowest Rated Questions
2. Participant Feedback

Overall Assessment

Provide one concluding paragraph summarizing the event's effectiveness and opportunities for improvement.

==================================================
STRICT OUTPUT RULES
===================

1. Return plain text only.

2. Do not use markdown.

3. Do not use:

*

**

#

###

*

_
bullet points

4. Use numbered lists only in the Recommendations section.

5. The first line of the response must be:

Executive Summary

6. Do not include:
   Overall Mean
   Overall Interpretation
   Category Results
   Question Results
   Top Rated Questions
   Lowest Rated Questions

These are already displayed elsewhere in the system.

7. Return only the requested sections.

";



// echo "<pre>";
// print_r($prompt);
// echo "</pre>";
// exit;

// var_dump(mb_check_encoding($prompt, 'UTF-8'));
// exit;
// echo "<pre>";
// echo "PROMPT LENGTH: " . strlen($prompt);
// echo "\n\n";
// echo $prompt;
// echo "</pre>";
// exit;

// var_dump($qualitativeStatus);

// echo "<pre>";
// echo $qualitativeText;
// echo "</pre>";
// exit;


try {


    //  Generate AI Summary via Ollama Llama3

    $ai_output = generateAISummary($prompt);

    $ai_output = str_replace('**', '', $ai_output);
    $ai_output = preg_replace('/^\-\s+/m', '', $ai_output);

    //  Optional: split recommendations section (if structured output is returned)
    $summary_text = $ai_output;
   

    //  Store summary into ai_summary table
    $summary_text = mysqli_real_escape_string($conn, $summary_text);
    // $recommendations = $recommendations ? mysqli_real_escape_string($conn, $recommendations) : 'NULL';

    $query = "
        INSERT INTO ai_summary (event_id, summary_text)
        VALUES ('$event_id', '$summary_text')
        ON DUPLICATE KEY UPDATE
          summary_text = VALUES(summary_text),
          generated_on = NOW()
    ";

    if (mysqli_query($conn, $query)) {
        echo $ai_output; // matches your existing frontend output (plain text)
        notifyAISummaryGenerated($conn, $organizer_id,$event_title, $organizer_id, $event_id); //Notification
        exit;
    } else {
        echo "Error saving AI summary: " . mysqli_error($conn);
        exit;
    }

} catch (\Throwable $e) {

      echo "<pre>";

    echo "CLASS:\n";
    echo get_class($e);

    echo "\n\nMESSAGE:\n";
    echo $e->getMessage();

    // echo "\n\nFILE:\n";
    // echo $e->getFile();

    // echo "\n\nLINE:\n";
    // echo $e->getLine();

    // echo "\n\nTRACE:\n";
    // echo $e->getTraceAsString();

    file_put_contents(
        __DIR__ . '/ollama_error.log',
        date('Y-m-d H:i:s') .
        ' - ' .
        $e->getMessage() .
        PHP_EOL,
        FILE_APPEND
    );

    echo "</pre>";

    exit;
}




// catch (Exception $e) {
//     echo "AI Generation failed: " . $e->getMessage();
// }

