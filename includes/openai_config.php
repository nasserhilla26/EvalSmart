<?php
// require_once __DIR__ . '/../vendor/autoload.php';

// use OpenAI as OpenAIBase;

// // Connect to Ollama (local API)
// $openai_client = OpenAIBase::factory()
//     ->withApiKey('ollama') // dummy key; not required by Ollama 
//     ->withBaseUri('http://localhost:11434/v1')
//     ->make();



define('OLLAMA_URL', 'http://localhost:11434/v1/chat/completions');
define('OLLAMA_MODEL', 'qwen2.5:7b');

function generateAISummary($prompt)
{
    $payload = [
        'model' => OLLAMA_MODEL,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are an Educational Quality Assurance Analyst specializing in event evaluation, accreditation reporting, survey analysis, statistical interpretation, and thematic analysis. Generate objective, evidence-based reports using only the provided evaluation data. Never invent facts or unsupported conclusions.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'stream' => false,
        'max_tokens' => 500
    ];

    $ch = curl_init(OLLAMA_URL);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 180,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ollama'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    // $response = curl_exec($ch);

    $start = microtime(true);

    $response = curl_exec($ch);

    $elapsed = microtime(true) - $start;

    echo "Execution Time: " . round($elapsed, 2) . " seconds";

    if (curl_errno($ch))
    {
        throw new Exception(
            'cURL Error: ' . curl_error($ch)
        );
    }

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    unset($ch);

    if ($httpCode !== 200)
    {
        throw new Exception(
            "Ollama returned HTTP {$httpCode}: {$response}"
        );
    }

    $result = json_decode(
        $response,
        true
    );

    if (
        !isset(
            $result['choices'][0]['message']['content']
        )
    )
    {
        throw new Exception(
            'Invalid Ollama response: ' . $response
        );
    }

    return trim(
        $result['choices'][0]['message']['content']
    );
}
