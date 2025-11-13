<?php
require '../vendor/autoload.php'; // Dompdf
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2); // Organizer only
include '../includes/db_connect.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Get Event ID
$event_id = intval($_POST['event_id']);
if (!$event_id) {
    die("Invalid event ID.");
}

// 1. Fetch Event Info
$eventQuery = mysqli_query($conn, "
    SELECT e.event_title, e.event_date, u.first_name, u.last_name, u.department
    FROM events e
    JOIN users u ON e.organizer_id = u.user_id
    WHERE e.event_id = '$event_id'
");
$event = mysqli_fetch_assoc($eventQuery);

// 2. Count Respondents
$resCountQuery = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) AS total FROM evaluation_answers WHERE event_id='$event_id'");
$resCount = mysqli_fetch_assoc($resCountQuery)['total'] ?? 0;

// 3. Fetch Evaluation Summary
$summaryQuery = mysqli_query($conn, "
    SELECT q.question_text, 
           ROUND(AVG(ea.answer_text), 2) AS average
    FROM evaluation_answers ea
    JOIN questionnaire_questions q ON ea.question_id = q.question_id
    WHERE ea.event_id = '$event_id'
    GROUP BY q.question_text
");
$summaryRows = '';
while ($row = mysqli_fetch_assoc($summaryQuery)) {
    $summaryRows .= "
        <tr>
            <td>{$row['question_text']}</td>
            <td style='text-align:center'>{$row['average']}</td>
        </tr>";
}

// 4. Fetch AI Summary (if exists)
$aiQuery = mysqli_query($conn, "SELECT summary_text, recommendations, generated_on FROM ai_summary WHERE event_id='$event_id' LIMIT 1");
$ai = mysqli_fetch_assoc($aiQuery);

// 5. Build HTML for PDF
$eventTitle = htmlspecialchars($event['event_title']);
$organizerName = htmlspecialchars($event['first_name'] . ' ' . $event['last_name']);
$department = htmlspecialchars($event['department']);
$eventDate = date('F j, Y', strtotime($event['event_date']));
$generatedDate = date('F j, Y g:i A');

$aiSummary = $ai
    ? nl2br($ai['summary_text'] . "\n\n" . $ai['recommendations'])
    : '<em>No AI summary has been generated yet.</em>';

$html = "
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
h2, h3 { text-align: center; color: #003366; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #888; padding: 6px; vertical-align: top; }
th { background-color: #e0e6ef; }
.footer { margin-top: 30px; text-align: center; font-size: 10px; color: #777; }
.watermark { position: fixed; bottom: 20px; right: 20px; opacity: 0.1; font-size: 24px; }
.section-title { margin-top: 25px; font-weight: bold; color: #003366; border-bottom: 1px solid #ccc; }
</style>

<h2>EvalSmart Evaluation Report</h2>

<h3>{$eventTitle}</h3>

<p><strong>Date:</strong> {$eventDate}<br>
<strong>Organizer:</strong> {$organizerName}<br>
<strong>Department:</strong> {$department}<br>
<strong>Total Respondents:</strong> {$resCount}</p>

<div class='section-title'>Evaluation Summary</div>
<table>
<thead><tr><th>Question</th><th style='width:100px;text-align:center;'>Average Rating</th></tr></thead>
<tbody>{$summaryRows}</tbody>
</table>

<div class='section-title'>AI Summary & Recommendations</div>
<p>{$aiSummary}</p>

<div class='footer'>
Generated on {$generatedDate}<br>
Powered by <strong>EvalSmart</strong>
</div>
<div class='watermark'>EvalSmart</div>
";

// 6. Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "EvalSmart_Report_{$eventTitle}.pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
?>
