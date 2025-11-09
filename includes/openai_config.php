<?php
require_once __DIR__ . '/../vendor/autoload.php';

use OpenAI as OpenAIBase;

// ✅ Connect to Ollama (local API)
$openai_client = OpenAIBase::factory()
    ->withApiKey('ollama') // dummy key; not required by Ollama
    ->withBaseUri('http://localhost:11434/v1')
    ->make();
