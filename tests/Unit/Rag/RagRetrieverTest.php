<?php

declare(strict_types=1);

namespace Tests\Unit\Rag;

use App\Models\Vehicle;
use App\Rag\QdrantDocumentStoreResolver;
use App\Rag\RagRetriever;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use Tests\TestCase;

class RagRetrieverTest extends TestCase
{
    public function test_it_embeds_the_query_and_searches_the_model_collection(): void
    {
        $calls = [];
        $document = new Document('Internal vector document');
        $document->score = 0.92;
        $store = new RetrieverFakeVectorStore($calls, [$document]);
        $retriever = new RagRetriever(
            new RetrieverFakeEmbeddingsProvider($calls),
            $this->resolver($calls, $store),
        );

        $documents = $retriever->search(Vehicle::class, 'electric hatchback', 3);

        $this->assertSame([
            ['embedText', 'electric hatchback'],
            ['resolve', 'vehicle-documents'],
            ['similaritySearch', [0.1, 0.2]],
        ], $calls);
        $this->assertSame([$document], $documents);
    }

    /** @param list<mixed> $calls */
    private function resolver(array &$calls, VectorStoreInterface $store): QdrantDocumentStoreResolver
    {
        return new QdrantDocumentStoreResolver(
            'http://qdrant.test:6333',
            null,
            768,
            '5b0b2b54-e931-4ee2-9407-2e0a56add078',
            function (string $url, ?string $key, int $dimension) use (&$calls, $store): VectorStoreInterface {
                $calls[] = ['resolve', basename(rtrim($url, '/'))];

                return $store;
            },
        );
    }
}

class RetrieverFakeEmbeddingsProvider implements EmbeddingsProviderInterface
{
    /** @param list<mixed> $calls */
    public function __construct(private array &$calls) {}

    public function embedText(string $text): array
    {
        $this->calls[] = ['embedText', $text];

        return [0.1, 0.2];
    }

    public function embedDocument(Document $document): Document
    {
        return $document;
    }

    public function embedDocuments(array $documents): array
    {
        return $documents;
    }
}

class RetrieverFakeVectorStore implements VectorStoreInterface
{
    /** @param list<mixed> $calls */
    /** @param list<Document> $documents */
    public function __construct(protected array &$calls, protected array $documents) {}

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
        $this->calls[] = ['similaritySearch', $embedding];

        return $this->documents;
    }
}
