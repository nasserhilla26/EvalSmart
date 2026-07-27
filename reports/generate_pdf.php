<?php

require_once '../includes/db_connect.php';
require_once '../includes/analytics_helper.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();

if (!isset($_GET['event_id'])) {
    die('Invalid Event');
}

$event_id = intval($_GET['event_id']);

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized Access');
}

// Logo

$baseUrl =
'http://localhost/evalsmart';

$logoPath = $baseUrl . '/assets/images/Pilar_logo.jpg';



// $file = __DIR__ . '/../assets/images/Pilar_College_seal.png';

// var_dump($file);
// echo "<br>";

// var_dump(file_exists($file));
// echo "<br>";

// var_dump(realpath($file));


// var_dump($logoPath);
// exit;

// Load Event
$eventQuery = mysqli_query($conn, "
    SELECT
        e.*,

        u.first_name,
        u.last_name,
        u.department,
        u.position

    FROM events e

    LEFT JOIN users u
        ON e.organizer_id = u.user_id

    WHERE e.event_id = '$event_id'
");

$event = mysqli_fetch_assoc($eventQuery);

if (!$event) {
    die('Event not found');
}


// Load Questions
$questionnaireQuery = mysqli_query($conn, "
    SELECT
        q.questionnaire_id,
        q.title,
        q.scale_id

    FROM questionnaire q

    INNER JOIN event_questionnaire eq
        ON q.questionnaire_id = eq.questionnaire_id

    WHERE eq.event_id = '$event_id'

    LIMIT 1
");

$questionnaire = mysqli_fetch_assoc(
    $questionnaireQuery
);


// Load Analytics
$scale_id = getEventScaleId(
    $conn,
    $event_id
);

$overallAverage = getOverallAverage(
    $conn,
    $event_id
);

$overallInterpretation = getInterpretation(
    $conn,
    $scale_id,
    $overallAverage
);

$totalResponses = getTotalResponses(
    $conn,
    $event_id
);

$categoryResults = getCategoryAverage(
    $conn,
    $event_id
);

$categorySD = getCategoryStandardDeviation(
    $conn,
    $event_id
);

$questionResults = getQuestionAverage(
    $conn,
    $event_id
);

$questionSD = getQuestionStandardDeviation(
    $conn,
    $event_id
);


// Scale Selected
$scaleQuery = mysqli_query($conn, "
    SELECT scale_name
    FROM evaluation_scales
    WHERE scale_id = '$scale_id'
");

$scale = mysqli_fetch_assoc(
    $scaleQuery
);


// Best & Lowest Category
$bestCategory = null;
$lowestCategory = null;

if (!empty($categoryResults))
{
    $bestCategory = $categoryResults[0];
    $lowestCategory = $categoryResults[0];

    foreach ($categoryResults as $cat)
    {
        if ($cat['average'] > $bestCategory['average'])
        {
            $bestCategory = $cat;
        }

        if ($cat['average'] < $lowestCategory['average'])
        {
            $lowestCategory = $cat;
        }
    }
}


// Top and Bottom Questions
$rankedQuestions = $questionResults;

usort(
    $rankedQuestions,
    fn($a, $b) =>
        $b['average'] <=> $a['average']
);

$topQuestions = array_slice(
    $rankedQuestions,
    0,
    5
);

$bottomQuestions = array_slice(
    array_reverse($rankedQuestions),
    0,
    5
);


// AI SUmmarry

$aiSummary = '';

$summaryQuery = mysqli_query($conn,"
    SELECT summary_text
    FROM ai_summary
    WHERE event_id = '$event_id'
    LIMIT 1
");

if ($row = mysqli_fetch_assoc($summaryQuery))
{
    $aiSummary = $row['summary_text'];
}


// Report ID counts
$reportId =
    'EVR-' .
    date('Y') .
    '-' .
    str_pad(
        $event_id,
        5,
        '0',
        STR_PAD_LEFT
    );


// Render PDF
ob_start();

include 'report_template.php';

$html = ob_get_clean();



// DOMPdf
$options = new Options();

$options->set(
    'isRemoteEnabled',
    true
);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper(
    'A4',
    'portrait'
);

$dompdf->render();

// page number
$canvas = $dompdf->getCanvas();

$canvas->page_text(
    380,
    810,
    "Report ID: {$reportId} | Page {PAGE_NUM} of {PAGE_COUNT}",
    null,
    9
);



// generate file name
$filename =
    'Event_Report_' .
    $event_id .
    '.pdf';

$dompdf->stream(
    $filename,
    [
        'Attachment' => true
    ]
);

