<?php

declare(strict_types=1);

namespace App\Rag;

use Closure;
use NeuronAI\RAG\VectorStore\QdrantVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;

class QdrantDocumentStoreResolver
{
    /** @var Closure(string, ?string, int): VectorStoreInterface */
    private Closure $storeFactory;

    /** @var array<string, QdrantDocumentStore> */
    private array $stores = [];

    public function __construct(
        private string $baseUrl,
        private ?string $key,
        private int $dimension,
        private string $pointIdNamespace,
        ?Closure $storeFactory = null,
    ) {
        $this->storeFactory = $storeFactory ?? fn (string $url, ?string $key, int $dimension): QdrantVectorStore => new QdrantVectorStore(
            $url,
            $key,
            dimension: $dimension,
        );
    }

    public function forCollection(string $collection): QdrantDocumentStore
    {
        if (isset($this->stores[$collection])) {
            return $this->stores[$collection];
        }

        $store = ($this->storeFactory)(
            rtrim($this->baseUrl, '/').'/collections/'.$collection.'/',
            $this->key,
            $this->dimension,
        );

        return $this->stores[$collection] = new QdrantDocumentStore($store, $this->pointIdNamespace);
    }
}
