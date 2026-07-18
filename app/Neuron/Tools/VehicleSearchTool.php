<?php

declare(strict_types=1);

namespace App\Neuron\Tools;

use App\Models\Vehicle;
use App\Rag\RagRetriever;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use NeuronAI\Tools\ToolPropertyInterface;

class VehicleSearchTool extends Tool
{
    /** @var list<array{record: array<string, mixed>, score: float}> */
    private array $results = [];

    private readonly SearchModelTool $search;

    public function __construct(RagRetriever $retriever)
    {
        $this->search = new SearchModelTool($retriever, Vehicle::class);

        parent::__construct(
            name: 'vehicle_search',
            description: 'Search globally available vehicles. This tool is read-only.',
        );
    }

    /** @return array|ToolPropertyInterface[] */
    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'query',
                type: PropertyType::STRING,
                description: 'Natural-language vehicle search query.',
                required: true,
            ),
            new ToolProperty(
                name: 'limit',
                type: PropertyType::INTEGER,
                description: 'Maximum number of vehicle results to return.',
            ),
        ];
    }

    /** @return list<array{record: array<string, mixed>, score: float}> */
    public function __invoke(
        string $query,
        ?int $limit = null,
    ): array {
        return $this->results = $this->search->search($query, $limit ?? 5);
    }

    /** @return list<array{record: array<string, mixed>, score: float}> */
    public function results(): array
    {
        return $this->results;
    }
}
