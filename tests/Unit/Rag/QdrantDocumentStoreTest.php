<?php

declare(strict_types=1);

namespace Tests\Unit\Rag;

use App\Rag\QdrantDocumentStore;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class QdrantDocumentStoreTest extends TestCase
{
    public function test_point_ids_are_deterministic_uuidv5_values_for_distinct_application_keys(): void
    {
        $namespace = '5b0b2b54-e931-4ee2-9407-2e0a56add078';
        $store = new QdrantDocumentStore(new RecordingVectorStore, $namespace);

        $vehicleId = $store->pointId('vehicle:42');

        $this->assertTrue(Uuid::isValid($vehicleId));
        $this->assertSame($vehicleId, $store->pointId('vehicle:42'));
        $this->assertNotSame($vehicleId, $store->pointId('fix:42'));
        $this->assertSame(5, Uuid::fromString($vehicleId)->getVersion());
    }

    public function test_point_ids_change_when_the_configured_namespace_changes(): void
    {
        $key = 'vehicle:42';
        $firstStore = new QdrantDocumentStore(
            new RecordingVectorStore,
            '5b0b2b54-e931-4ee2-9407-2e0a56add078',
        );
        $secondStore = new QdrantDocumentStore(
            new RecordingVectorStore,
            '6b0b2b54-e931-4ee2-9407-2e0a56add078',
        );

        $this->assertSame($firstStore->pointId($key), $firstStore->pointId($key));
        $this->assertNotSame($firstStore->pointId($key), $secondStore->pointId($key));
        $this->assertSame(
            Uuid::uuid5('5b0b2b54-e931-4ee2-9407-2e0a56add078', $key)->toString(),
            $firstStore->pointId($key),
        );
        $this->assertSame(
            Uuid::uuid5('6b0b2b54-e931-4ee2-9407-2e0a56add078', $key)->toString(),
            $secondStore->pointId($key),
        );
    }

    public function test_adding_a_document_maps_its_id_and_preserves_its_source_key(): void
    {
        $delegate = new RecordingVectorStore;
        $store = new QdrantDocumentStore($delegate, '5b0b2b54-e931-4ee2-9407-2e0a56add078');
        $document = new Document('Vehicle document');
        $document->sourceName = 'vehicle:42';

        $store->addDocument($document);

        $this->assertSame($store->pointId('vehicle:42'), $delegate->documents[0]->id);
        $this->assertSame('vehicle:42', $delegate->documents[0]->sourceName);
    }
}

class RecordingVectorStore implements VectorStoreInterface
{
    /** @var list<Document> */
    public array $documents = [];

    public function addDocument(Document $document): VectorStoreInterface
    {
        return $this->addDocuments([$document]);
    }

    public function addDocuments(array $documents): VectorStoreInterface
    {
        $this->documents = $documents;

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
