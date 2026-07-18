<?php

declare(strict_types=1);

namespace Tests\Unit\Rag;

use App\Rag\QdrantDocumentStoreResolver;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use Tests\TestCase;

class QdrantDocumentStoreResolverTest extends TestCase
{
    public function test_it_builds_collection_specific_stores_with_shared_connection_settings(): void
    {
        $stores = [];
        $resolver = new QdrantDocumentStoreResolver(
            baseUrl: 'http://qdrant.test:6333/',
            key: 'test-key',
            dimension: 768,
            pointIdNamespace: '6b0b2b54-e931-4ee2-9407-2e0a56add078',
            storeFactory: function (string $url, ?string $key, int $dimension) use (&$stores): VectorStoreInterface {
                $stores[] = [$url, $key, $dimension];

                return new ResolverRecordingVectorStore;
            },
        );

        $vehicleStore = $resolver->forCollection('vehicle-documents');
        $fixStore = $resolver->forCollection('fix-documents');

        $this->assertSame([
            ['http://qdrant.test:6333/collections/vehicle-documents/', 'test-key', 768],
            ['http://qdrant.test:6333/collections/fix-documents/', 'test-key', 768],
        ], $stores);
        $this->assertSame(
            '6b0b2b54-e931-4ee2-9407-2e0a56add078',
            $this->property($vehicleStore, 'pointIdNamespace'),
        );
        $this->assertSame(
            '6b0b2b54-e931-4ee2-9407-2e0a56add078',
            $this->property($fixStore, 'pointIdNamespace'),
        );
    }

    private function property(object $object, string $property): mixed
    {
        return (new \ReflectionProperty($object, $property))->getValue($object);
    }
}

class ResolverRecordingVectorStore implements VectorStoreInterface
{
    public function addDocument(Document $document): VectorStoreInterface
    {
        return $this;
    }

    public function addDocuments(array $documents): VectorStoreInterface
    {
        return $this;
    }

    public function deleteBySource(string $sourceType, string $sourceName): VectorStoreInterface
    {
        return $this;
    }

    public function deleteBy(string $sourceType, ?string $sourceName = null): VectorStoreInterface
    {
        return $this;
    }

    public function similaritySearch(array $embedding): iterable
    {
        return [];
    }
}
