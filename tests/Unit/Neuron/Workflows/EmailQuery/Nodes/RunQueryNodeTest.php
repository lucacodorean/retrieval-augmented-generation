<?php

declare(strict_types=1);

namespace Tests\Unit\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Agents\VehicleAgent;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Events\QueryObtainedEvent;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use App\Neuron\Workflows\EmailQuery\Nodes\RunQueryNode;
use Mockery;
use NeuronAI\Workflow\Events\StartEvent;
use Tests\TestCase;

class RunQueryNodeTest extends TestCase
{
    public function test_it_runs_the_query_and_stores_the_complete_response(): void
    {
        $query = 'Which electric vehicles are available?';
        $response = [
            'response' => [
                'natural-lang' => 'Two electric vehicles are available.',
                'serialized' => [
                    ['record' => ['type' => 'vehicle', 'id' => 42], 'score' => 0.91],
                ],
            ],
        ];
        $state = new EmailQueryWorkflowState;
        $vehicleAgent = Mockery::mock(VehicleAgent::class);
        $vehicleAgent->shouldReceive('ask')
            ->once()
            ->with($query)
            ->andReturnUsing(function () use ($state, $response): array {
                $this->assertSame(NodeState::QUERY_IN_PROGRESS, $state->currentStep());

                return $response;
            });
        $node = new RunQueryNode($query, $vehicleAgent);

        $event = $node(new StartEvent, $state);

        $this->assertInstanceOf(QueryObtainedEvent::class, $event);
        $this->assertSame($response, $state->originalResponse());
        $this->assertSame('Two electric vehicles are available.', $state->sourceText());
        $this->assertSame(NodeState::QUERY_OBTAINED, $state->currentStep());
    }
}
