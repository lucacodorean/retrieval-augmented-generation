<?php

declare(strict_types=1);

namespace App\Rag;

use App\Rag\Contracts\RagSearchable;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;

class RagRetriever
{
    public function __construct(
        private readonly EmbeddingsProviderInterface $embeddings,
        private readonly QdrantDocumentStoreResolver $stores,
    ) {}

    /** @return list<Document> */
    public function search(string $modelClass, string $query, int $limit): array
    {
        /** @var class-string<RagSearchable> $modelClass */
        $embedding = $this->embeddings->embedText($query);
        $documents = $this->stores
            ->forCollection($modelClass::ragCollection())
            ->similaritySearch($embedding);

        return array_slice(iterator_to_array($documents, false), 0, $limit);
    }
}
