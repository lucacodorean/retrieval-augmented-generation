<?php

declare(strict_types=1);

namespace Tests\Feature\Neuron\Tools;

use App\Models\Vehicle;
use App\Neuron\Resources\VehicleResource;
use App\Neuron\Tools\VehicleSearchTool;
use App\Rag\RagRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NeuronAI\RAG\Document;
use Tests\TestCase;

class VehicleSearchToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_a_native_read_only_vehicle_search_tool_and_captures_its_results(): void
    {
        $vehicle = Vehicle::factory()->create();
        $retriever = new VehicleSearchToolFakeRetriever([$this->document($vehicle->getKey(), 0.98)]);
        $tool = new VehicleSearchTool($retriever);
        $tool->setInputs(['query' => 'electric vehicles', 'limit' => 3]);

        $tool->execute();

        $resource = new VehicleResource;
        $this->assertSame('vehicle_search', $tool->getName());
        $this->assertSame(['query'], $tool->getRequiredProperties());
        $this->assertSame(['query', 'limit'], array_map(
            static fn ($property): string => $property->getName(),
            $tool->getProperties(),
        ));
        $this->assertSame([['electric vehicles', 3]], $retriever->calls);
        $this->assertSame([
            [
                'record' => $resource->toArray($vehicle->fresh('vehicleDetails')),
                'score' => 0.98,
            ],
        ], $tool->results());
        $this->assertSame(json_encode($tool->results(), JSON_THROW_ON_ERROR), $tool->getResult());
    }

    private function document(int $vehicleId, float $score): Document
    {
        $document = new Document('Internal vector document');
        $document->metadata = ['vehicle_id' => $vehicleId];
        $document->score = $score;

        return $document;
    }
}

class VehicleSearchToolFakeRetriever extends RagRetriever
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
