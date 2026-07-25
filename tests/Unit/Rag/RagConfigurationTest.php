<?php

declare(strict_types=1);

namespace Tests\Unit\Rag;

use Tests\TestCase;

class RagConfigurationTest extends TestCase
{
    public function test_local_rag_defaults_match_the_supported_services(): void
    {
        $this->assertSame('nomic-embed-text', config('rag.ollama.model'));
        $this->assertSame(180.0, config('rag.ollama.timeout'));
        $this->assertSame(768, config('rag.qdrant.dimension'));
        $this->assertSame('http://qdrant:6333', config('rag.qdrant.base_url'));
        $this->assertSame('5b0b2b54-e931-4ee2-9407-2e0a56add078', config('rag.qdrant.point_id_namespace'));
    }
}
