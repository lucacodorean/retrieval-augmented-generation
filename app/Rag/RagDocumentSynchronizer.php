<?php

declare(strict_types=1);

namespace App\Rag;

use App\Rag\Contracts\Documentable;
use Illuminate\Database\Eloquent\Model;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;

class RagDocumentSynchronizer
{
    public function __construct(
        private EmbeddingsProviderInterface $embeddings,
        private QdrantDocumentStoreResolver $stores,
    ) {
        //
    }

    public function upsert(Documentable&Model $model): void
    {
        $transformer = $model::documentTransformer();
        $document = $this->embeddings->embedDocument($transformer::build($model));

        $this->stores->forCollection($model::ragCollection())
            ->deleteBy($document->sourceType, $document->sourceName)
            ->addDocument($document);
    }

    public function delete(string $modelClass, string $documentKey): void
    {
        $this->stores->forCollection($modelClass::ragCollection())
            ->deleteBy($modelClass, $documentKey);
    }
}
