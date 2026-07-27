<?php

include '../includes/db_connect.php';
include '../includes/openai_config.php'; // Ollama connection
include '../includes/analytics_helper.php';


header('Content-Type: text/plain; charset=UTF-8');

$event_id = 13;
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


$url = "http://localhost:11434/v1/chat/completions";

$prompt = "

Analyze the following event evaluation data and generate a professional event evaluation report.

You are evaluating an academic, institutional, training, seminar, workshop, outreach, extension, or school-related event.

==================================================
EVENT INFORMATION
==================================================

Event Title:
{$event['event_title']}

Event Description:
{$event['event_description']}

Event Date:
{$event['event_date']}

Event Venue:
{$event['event_venue']}

==================================================
EVALUATION RESULTS
==================================================

Total Responses:
{$totalResponses}

Overall Mean:
{$overallAverage}

Overall Interpretation:
{$overallInterpretation}

==================================================
CATEGORY RESULTS
==================================================

{$categoryText}

==================================================
QUESTION RESULTS
==================================================

{$questionText}

==================================================
TOP RATED QUESTIONS
==================================================

{$topQuestionsText}

==================================================
LOWEST RATED QUESTIONS
==================================================

{$lowestQuestionsText}

==================================================
PARTICIPANT COMMENTS AND SUGGESTIONS
==================================================

NO COMMENTS AVAILABLE

==================================================
TASKS
==================================================

1. Analyze the quantitative results including:
   - Overall Mean
   - Overall Interpretation
   - Category Means
   - Category Interpretations
   - Question Means
   - Question Interpretations

2. Perform thematic analysis on all participant comments and suggestions.

3. Identify recurring positive themes.

4. Identify recurring improvement themes.

5. Determine strengths based on quantitative and qualitative evidence.

6. Determine areas needing improvement based on quantitative and qualitative evidence.

7. Generate actionable recommendations.

==================================================
REPORT FORMAT
==================================================

Event Evaluation Report

Executive Summary

Write one to two concise paragraphs summarizing the overall evaluation results, major strengths, and areas needing improvement.

Quantitative Findings

Overall Mean:
[Mean]

Overall Interpretation:
[Interpretation]

Total Responses:
[Number]

Category Performance

1. [Category Name]
Mean: [Mean]
Interpretation: [Interpretation]

2. [Category Name]
Mean: [Mean]
Interpretation: [Interpretation]

Highest Rated Category

[Category Name]
Mean: [Mean]
Interpretation: [Interpretation]

Lowest Rated Category

[Category Name]
Mean: [Mean]
Interpretation: [Interpretation]

Question-Level Insights

Discuss the strongest and weakest evaluation criteria based on the provided question results.

Positive Themes

Identify and explain recurring positive themes from comments and suggestions.

Example:

1. Speaker Expertise
Participants consistently appreciated the expertise, preparedness, and clarity of the speakers.

2. Event Organization
Participants highlighted the smooth flow of activities and effective event management.

Improvement Themes

Identify and explain recurring concerns or suggestions.

Example:

1. Internet Connectivity
Several participants reported connectivity issues during activities requiring internet access.

2. Time Management
Participants suggested allocating more time for discussions and interactive activities.

Recommendations

Provide 3 to 5 actionable recommendations directly linked to the quantitative findings and thematic analysis.

Overall Assessment

Provide a concluding paragraph summarizing the effectiveness of the event and opportunities for future improvement.

==================================================
IMPORTANT RULES
==================================================

1. Base all findings strictly on the provided evaluation data.

2. Do not invent facts, facilities, services, activities, achievements, equipment, or event details that are not explicitly mentioned.

3. Do not assume reasons for ratings unless supported by participant comments or suggestions.

4. Use the provided interpretations exactly as given.

5. If evidence is insufficient, use neutral language such as:
   'Participants generally rated this area positively.'

6. Avoid exaggerated praise.

7. Maintain an objective and professional tone.

8. Write in a style appropriate for:
   - Accreditation Reports
   - Quality Assurance Reports
   - Institutional Assessment Reports
   - Administrative Reports

9. Do not use markdown.

10. DO NOT use:
    *
    **
    #
    ###
    -
    _
    Bullet points

11. Use numbered lists only.

12. Return only the report.

";

$data = [
    "model" => "llama3:latest",
    "messages" => [
        [
            "role" => "user",
            "content" => $prompt
        ]
    ]
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer ollama"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

echo "<pre>";

if (curl_errno($ch)) {
    echo "CURL ERROR:\n";
    echo curl_error($ch);
} else {
    echo $response;
    exit;
}

curl_close($ch);
exit;