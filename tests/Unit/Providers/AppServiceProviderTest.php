<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Rag\QdrantDocumentStoreResolver;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use ReflectionProperty;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_embeddings_provider_binding_resolves_ollama_with_configured_model_and_url(): void
    {
        config()->set('rag.ollama.model', 'test-embedding-model');
        config()->set('rag.ollama.url', 'http://ollama.test/api');

        $provider = app(EmbeddingsProviderInterface::class);

        $this->assertInstanceOf(OllamaEmbeddingsProvider::class, $provider);
        $this->assertSame('test-embedding-model', $this->property($provider, 'model'));
        $this->assertSame('http://ollama.test/api', $this->property($provider, 'url'));
    }

    public function test_document_store_resolver_binding_uses_the_shared_qdrant_configuration(): void
    {
        config()->set('rag.qdrant.base_url', 'http://qdrant.test:6333');
        config()->set('rag.qdrant.key', 'test-key');
        config()->set('rag.qdrant.dimension', 768);
        config()->set('rag.qdrant.point_id_namespace', '6b0b2b54-e931-4ee2-9407-2e0a56add078');

        $resolver = app(QdrantDocumentStoreResolver::class);

        $this->assertInstanceOf(QdrantDocumentStoreResolver::class, $resolver);
        $this->assertSame('http://qdrant.test:6333', $this->property($resolver, 'baseUrl'));
        $this->assertSame('test-key', $this->property($resolver, 'key'));
        $this->assertSame(768, $this->property($resolver, 'dimension'));
        $this->assertSame(
            '6b0b2b54-e931-4ee2-9407-2e0a56add078',
            $this->property($resolver, 'pointIdNamespace'),
        );
    }

    private function property(object $object, string $property): mixed
    {
        $reflection = new ReflectionProperty($object, $property);

        return $reflection->getValue($object);
    }
}
