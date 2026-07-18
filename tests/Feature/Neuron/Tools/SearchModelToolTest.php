<?php

declare(strict_types=1);

namespace Tests\Feature\Neuron\Tools;

use App\Models\Vehicle;
use App\Neuron\Resources\VehicleResource;
use App\Neuron\Tools\SearchModelTool;
use App\Rag\RagRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NeuronAI\RAG\Document;
use Tests\TestCase;

class SearchModelToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_search_returns_current_safe_records_in_qdrant_score_order_and_omits_missing_records(): void
    {
        $first = Vehicle::factory()->create();
        $second = Vehicle::factory()->create();
        $missingId = $second->getKey() + 100;
        $retriever = new SearchModelToolFakeRetriever([
            $this->document($second->getKey(), 0.98),
            $this->document($missingId, 0.95),
            $this->document($first->getKey(), 0.91),
        ]);
        $tool = new SearchModelTool($retriever, Vehicle::class);

        $results = $tool->search('efficient vehicles');

        $this->assertSame([['efficient vehicles', 5]], $retriever->calls);
        $resource = new VehicleResource;
        $this->assertSame([
            [
                'record' => $resource->toArray($second->fresh('vehicleDetails')),
                'score' => 0.98,
            ],
            [
                'record' => $resource->toArray($first->fresh('vehicleDetails')),
                'score' => 0.91,
            ],
        ], $results);
        $serialized = json_encode($results, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('user', $serialized);
        $this->assertStringNotContainsString('owner', $serialized);
        $this->assertStringNotContainsString('name', $serialized);
        $this->assertStringNotContainsString('email', $serialized);
        $this->assertStringNotContainsString('embedding', $serialized);
        $this->assertStringNotContainsString('Internal vector document', $serialized);
    }

    private function document(int $vehicleId, float $score): Document
    {
        $document = new Document('Internal vector document');
        $document->metadata = ['vehicle_id' => $vehicleId];
        $document->score = $score;

        return $document;
    }
}

class SearchModelToolFakeRetriever extends RagRetriever
{
    /** @var list<array{0: string, 1: int}> */
    public array $calls = [];

    /** @param list<Document> $documents */
    public function __construct(private array $documents) {}

    public function search(string $modelClass, string $query, int $limit): array
    {
        $this->calls[] = [$query, $limit];

        return $this->documents;
    }
}
