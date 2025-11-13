<?php
include '../includes/auth.php';
include '../includes/role_check.php';
require_role(2);
include '../includes/db_connect.php';
include '../includes/openai_config.php'; // Ollama connection

header('Content-Type: text/plain; charset=UTF-8');

$event_id = intval($_POST['event_id']);
if (!$event_id) {
    echo "Error: Missing event ID.";
    exit;
}

// 🧩 Collect all responses
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

// 🧠 Prepare AI prompt
$prompt = "
You are EvalSmart AI, an academic feedback summarizer.
Given the following participant evaluations, create:

1. A concise 2–3 paragraph summary of overall feedback.
2. A bullet list of the event’s key strengths.
3. A bullet list of the event’s key weaknesses.
4. A bullet list of recommendations for improvement.
Be polite, professional, and clear.

Responses:
$textData
";

try {
    // 🧠 Generate AI Summary via Ollama Llama3
    $response = $openai_client->chat()->create([
        'model' => 'llama3',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful AI assistant for event feedback.'],
            ['role' => 'user', 'content' => $prompt]
        ],
    ]);

    $ai_output = trim($response->choices[0]->message->content ?? '');

    if (empty($ai_output)) {
        echo "Error: No response from AI model.";
        exit;
    }

    // 🪶 Optional: split recommendations section (if structured output is returned)
    $summary_text = $ai_output;
    $recommendations = null;

    if (preg_match('/Recommendations:(.*)/is', $ai_output, $matches)) {
        $recommendations = trim($matches[1]);
        $summary_text = trim(str_replace($matches[0], '', $ai_output));
    }

    // 🗄️ Store summary into ai_summary table
    $summary_text = mysqli_real_escape_string($conn, $summary_text);
    $recommendations = $recommendations ? mysqli_real_escape_string($conn, $recommendations) : 'NULL';

    $query = "
        INSERT INTO ai_summary (event_id, summary_text, recommendations)
        VALUES ('$event_id', '$summary_text', $recommendations)
        ON DUPLICATE KEY UPDATE
          summary_text = VALUES(summary_text),
          recommendations = VALUES(recommendations),
          generated_on = NOW()
    ";

    if (mysqli_query($conn, $query)) {
        echo $ai_output; // 👈 matches your existing frontend output (plain text)
    } else {
        echo "Error saving AI summary: " . mysqli_error($conn);
    }

} catch (Exception $e) {
    echo "AI Generation failed: " . $e->getMessage();
}
?>
