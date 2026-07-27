<?php

declare(strict_types=1);

return [
    'provider' => env('AGENT_PROVIDER', 'ollama'),
    'model' => env('AGENT_MODEL'),
    'api_key' => env('AGENT_API_KEY'),
    'base_url' => env('AGENT_BASE_URL', 'http://host.docker.internal:11434/api'),
    'timeout' => (float) env('AGENT_TIMEOUT', 180),
];
