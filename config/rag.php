<?php

declare(strict_types=1);

return [
    'ollama' => [
        'url' => env('RAG_OLLAMA_URL', 'http://host.docker.internal:11434/api'),
        'model' => env('RAG_OLLAMA_MODEL', 'nomic-embed-text'),
        'timeout' => (float) env('RAG_OLLAMA_TIMEOUT', 180),
    ],
    'qdrant' => [
        'base_url' => env('RAG_QDRANT_BASE_URL', 'http://qdrant:6333'),
        'key' => env('RAG_QDRANT_KEY'),
        'point_id_namespace' => env('RAG_QDRANT_POINT_ID_NAMESPACE', '5b0b2b54-e931-4ee2-9407-2e0a56add078'),
        'dimension' => (int) env('RAG_EMBEDDING_DIMENSION', 768),
    ],
];
