<?php

declare(strict_types=1);

namespace Tests\Feature\Neuron\Agents;

use App\Models\Vehicle;
use App\Neuron\Agents\VehicleAgent;
use App\Neuron\Resources\VehicleResource;
use App\Neuron\Tools\VehicleSearchTool;
use App\Rag\RagRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\HttpClient\AmpHttpClient;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\RAG\Document;
use NeuronAI\Testing\FakeAIProvider;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class VehicleAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_generated_text_and_serialized_vehicle_results_from_a_tool_call(): void
    {
        $vehicle = Vehicle::factory()->create();
        $retriever = new VehicleAgentFakeRetriever([$this->document($vehicle->getKey(), 0.98)]);
        $tool = new VehicleSearchTool(new VehicleAgentFakeRetriever([]));
        $executedTool = new VehicleSearchTool($retriever);
        $provider = new FakeAIProvider(
            new ToolCallMessage(tools: [$executedTool->setInputs(['query' => 'electric vehicles'])]),
            new AssistantMessage('The matching vehicle is electric.'),
        );
        $agent = new VehicleAgent($tool);
        $agent->setAiProvider($provider);

        $response = $agent->ask('Which vehicles are electric?');

        $resource = new VehicleResource;
        $this->assertSame([
            'response' => [
                'natural-lang' => 'The matching vehicle is electric.',
                'serialized' => [[
                    'record' => $resource->toArray($vehicle->fresh('vehicleDetails')),
                    'score' => 0.98,
                ]],
            ],
        ], $response);
        $this->assertSame([['electric vehicles', 5]], $retriever->calls);
        $provider->assertCallCount(2);
        $provider->assertToolsConfigured(['vehicle_search']);
    }

    public function test_it_returns_an_empty_serialized_list_when_the_model_does_not_search(): void
    {
        $tool = new VehicleSearchTool(new VehicleAgentFakeRetriever([]));
        $provider = new FakeAIProvider(new AssistantMessage('I can help with vehicle questions.'));
        $agent = new VehicleAgent($tool);
        $agent->setAiProvider($provider);

        $response = $agent->ask('Hello');

        $this->assertSame([
            'response' => [
                'natural-lang' => 'I can help with vehicle questions.',
                'serialized' => [],
            ],
        ], $response);
        $provider->assertCallCount(1);
    }

    public function test_the_production_provider_uses_the_configured_ollama_timeout(): void
    {
        $providerMethod = new ReflectionMethod(VehicleAgent::class, 'provider');
        $provider = $providerMethod->invoke(new VehicleAgent(
            new VehicleSearchTool(new VehicleAgentFakeRetriever([])),
        ));

        $this->assertInstanceOf(Ollama::class, $provider);
        $this->assertInstanceOf(AmpHttpClient::class, $provider->getHttpClient());

        $timeout = new ReflectionProperty(AmpHttpClient::class, 'timeout');
        $this->assertSame(180.0, $timeout->getValue($provider->getHttpClient()));
    }

    private function document(int $vehicleId, float $score): Document
    {
        $document = new Document('Internal vector document');
        $document->metadata = ['vehicle_id' => $vehicleId];
        $document->score = $score;

        return $document;
    }
}

class VehicleAgentFakeRetriever extends RagRetriever
{
    /** @var list<array{0: string, 1: int}> */
    public array $calls = [];

    /** @param list<Document> $documents */
    public function __construct(private array $documents) {}

    public function search(string $modelClass, string $query, int $limit, array $filters = []): array
    {
        $this->calls[] = [$query, $limit];

        return $this->documents;
    }
}
