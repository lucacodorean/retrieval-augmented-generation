<?php

declare(strict_types=1);

namespace App\Rag;

use NeuronAI\RAG\Document;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use Ramsey\Uuid\Uuid;

readonly class QdrantDocumentStore implements VectorStoreInterface
{
    public function __construct(
        private VectorStoreInterface $store,
        private string $pointIdNamespace,
    ) {}

    public function pointId(string $key): string
    {
        return Uuid::uuid5($this->pointIdNamespace, $key)->toString();
    }

    public function addDocument(Document $document): VectorStoreInterface
    {
        return $this->addDocuments([$document]);
    }

    public function addDocuments(array $documents): VectorStoreInterface
    {
        foreach ($documents as $document) {
            $document->id = $this->pointId($document->sourceName);
        }

        $this->store->addDocuments($documents);

        return $this;
    }

    public function deleteBySource(string $sourceType, string $sourceName): VectorStoreInterface
    {
        $this->store->deleteBySource($sourceType, $sourceName);

        return $this;
    }

    public function deleteBy(string $sourceType, ?string $sourceName = null): VectorStoreInterface
    {
        $this->store->deleteBy($sourceType, $sourceName);

        return $this;
    }

    public function similaritySearch(array $embedding): iterable
    {
        return $this->store->similaritySearch($embedding);
    }
}
