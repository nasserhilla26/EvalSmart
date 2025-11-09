<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';
include '../includes/openai_config.php'; // now pointing to Ollama connection

header('Content-Type: text/html; charset=UTF-8');

$event_id = intval($_POST['event_id']);

// 🧩 Collect responses
$query = mysqli_query($conn, "
  SELECT answer_text, comments, suggestions 
  FROM evaluation_answers 
  WHERE event_id = '$event_id'
");

$textData = '';
while ($row = mysqli_fetch_assoc($query)) {
  $textData .= "Answer: {$row['answer_text']}\n";
  if (!empty($row['comments'])) $textData .= "Comment: {$row['comments']}\n";
  if (!empty($row['suggestions'])) $textData .= "Suggestion: {$row['suggestions']}\n";
}

// 🧠 AI prompt for event evaluation summary
$prompt = "
You are EvalSmart AI, an academic feedback summarizer.
Given the following participant evaluations, create:

1. A concise 2–3 paragraph summary of overall feedback.
2. A bullet list of the event’s key strengths.
3. A bullet list of recommendations for improvement.
Be polite, professional, and clear.

Responses:
$textData
";

// 🔮 Send prompt to local Ollama (Llama3)
$response = $openai_client->chat()->create([
    'model' => 'llama3',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful AI assistant for event feedback.'],
        ['role' => 'user', 'content' => $prompt]
    ],
]);

echo nl2br($response->choices[0]->message->content);
?>
