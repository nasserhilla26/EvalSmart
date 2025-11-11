<?php
include '../includes/auth.php';
include '../includes/db_connect.php';

$event_id = intval($_GET['event_id'] ?? 0);
$user_id  = intval($_GET['user_id'] ?? 0);

$query = "
SELECT qq.question_text, ea.answer_text, ea.comments, ea.suggestions
FROM evaluation_answers ea
JOIN questionnaire_questions qq ON ea.question_id = qq.question_id
WHERE ea.event_id = '$event_id' AND ea.user_id = '$user_id'
ORDER BY qq.question_id ASC
";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
  echo "<p class='text-center text-muted'>No responses found for this evaluator.</p>";
  exit;
}

echo "<ul class='list-group'>";
while ($r = mysqli_fetch_assoc($result)) {
  echo "<li class='list-group-item'>
          <strong>" . htmlspecialchars($r['question_text']) . "</strong><br>
          <span class='text-muted'> Answer: " . htmlspecialchars($r['answer_text']) . "</span>
        </li>
        ";
}

echo "</ul>";

// 🔹 Fetch overall comments and suggestions from evaluator (optional grouping)
$feedbackQuery = "
SELECT DISTINCT comments, suggestions
FROM evaluation_answers
WHERE event_id = '$event_id' AND user_id = '$user_id'
  AND (comments IS NOT NULL OR suggestions IS NOT NULL)
";
$feedbackResult = mysqli_query($conn, $feedbackQuery);

if (mysqli_num_rows($feedbackResult) > 0) {
    $fb = mysqli_fetch_assoc($feedbackResult);
    echo "<div class='card  mt-3'>
            <div class='card-header fw-bold text-dark'>
              Additional Feedback
            </div>
            <div class='card-body'>
              <p><strong>Comments:</strong><br>" . (!empty($fb['comments']) ? htmlspecialchars($fb['comments']) : '<em>No comments provided.</em>') . "</p>
              <p><strong>Suggestions:</strong><br>" . (!empty($fb['suggestions']) ? htmlspecialchars($fb['suggestions']) : '<em>No suggestions provided.</em>') . "</p>
            </div>
          </div>";
}

echo "</div>";
?>



