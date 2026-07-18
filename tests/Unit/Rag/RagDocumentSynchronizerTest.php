<?php

declare(strict_types=1);

namespace Tests\Unit\Rag;

use App\Rag\Contracts\Documentable;
use App\Rag\Contracts\DocumentTransformer;
use App\Rag\QdrantDocumentStoreResolver;
use App\Rag\RagDocumentSynchronizer;
use Illuminate\Database\Eloquent\Model;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use Tests\TestCase;

class RagDocumentSynchronizerTest extends TestCase
{
    public function test_upsert_builds_embeds_deletes_and_adds_the_document_in_order(): void
    {
        $calls = [];
        $embeddings = new FakeEmbeddingsProvider($calls);
        $resolver = $this->resolver($calls);
        $model = new FakeDocumentableModel;
        FakeDocumentTransformer::$calls = &$calls;

        $synchronizer = new RagDocumentSynchronizer($embeddings, $resolver);

        $synchronizer->upsert($model);

        $this->assertSame([
            'build',
            'embedDocument',
            ['resolve', 'fake-documents'],
            ['deleteBy', FakeDocumentableModel::class, 'fake:document'],
            'addDocuments',
        ], $calls);
        $this->assertSame([1.0], $this->store($resolver)->addedDocument->embedding);
    }

    public function test_delete_removes_the_document_without_embedding(): void
    {
        $calls = [];
        $embeddings = new FakeEmbeddingsProvider($calls);
        $resolver = $this->resolver($calls);
        $synchronizer = new RagDocumentSynchronizer($embeddings, $resolver);

        $synchronizer->delete(FakeDocumentableModel::class, 'fake:document');

        $this->assertSame([
            ['resolve', 'fake-documents'],
            ['deleteBy', FakeDocumentableModel::class, 'fake:document'],
        ], $calls);
    }

    /** @param list<mixed> $calls */
    private function resolver(array &$calls): QdrantDocumentStoreResolver
    {
        return new QdrantDocumentStoreResolver(
            'http://qdrant.test:6333',
            null,
            768,
            '6b0b2b54-e931-4ee2-9407-2e0a56add078',
            function (string $url, ?string $key, int $dimension) use (&$calls): VectorStoreInterface {
                $calls[] = ['resolve', basename(rtrim($url, '/'))];

                return new FakeVectorStore($calls);
            },
        );
    }

    private function store(QdrantDocumentStoreResolver $resolver): FakeVectorStore
    {
        $store = $resolver->forCollection('fake-documents');

        return (new \ReflectionProperty($store, 'store'))->getValue($store);
    }
}

class FakeDocumentableModel extends Model implements Documentable
{
    public static function documentTransformer(): string
    {
        return FakeDocumentTransformer::class;
    }

    public static function documentRelations(): array
    {
        return [];
    }

    public function documentKey(): string
    {
        return 'fake:document';
    }

    public static function ragCollection(): string
    {
        return 'fake-documents';
    }
}

class FakeDocumentTransformer implements DocumentTransformer
{
    /** @var list<mixed> */
    public static array $calls = [];

    public static function build(Model $model): Document
    {
        self::$calls[] = 'build';

        $document = new Document('Fake document');
        $document->sourceType = FakeDocumentableModel::class;
        $document->sourceName = $model->documentKey();

        return $document;
    }
}

class FakeEmbeddingsProvider implements EmbeddingsProviderInterface
{
    /** @param list<mixed> $calls */
    public function __construct(private array &$calls) {}

    public function embedText(string $text): array
    {
        return [];
    }

    public function embedDocument(Document $document): Document
    {
        $this->calls[] = 'embedDocument';
        $document->embedding = [1.0];

        return $document;
    }

    public function embedDocuments(array $documents): array
    {
        return $documents;
    }
}

class FakeVectorStore implements VectorStoreInterface
{
    public ?Document $addedDocument = null;

    /** @param list<mixed> $calls */
    public function __construct(private array &$calls) {}

    public function addDocument(Document $document): VectorStoreInterface
    {
        $this->calls[] = 'addDocument';
        $this->addedDocument = $document;

        return $this;
    }

    public function addDocuments(array $documents): VectorStoreInterface
    {
        $this->calls[] = 'addDocuments';
        $this->addedDocument = $documents[0];

        return $this;
    }

    public function deleteBySource(string $sourceType, string $sourceName): VectorStoreInterface
    {
        return $this;
    }

    public function deleteBy(string $sourceType, ?string $sourceName = null): VectorStoreInterface
    {
        $this->calls[] = ['deleteBy', $sourceType, $sourceName];

        return $this;
    }

    public function similaritySearch(array $embedding): iterable
    {
        return [];
    }
}
