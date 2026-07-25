<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Agents\VehicleAgent;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Events\QueryObtainedEvent;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Node;

class RunQueryNode extends Node implements NodeInterface
{
    public function __construct(
        private readonly string $query,
        private readonly VehicleAgent $vehicleAgent
    ) {}

    public function __invoke(StartEvent $event, EmailQueryWorkflowState $state): QueryObtainedEvent
    {
        $state->setCurrentStep(NodeState::QUERY_IN_PROGRESS);

        $response = $this->vehicleAgent->ask($this->query);

        $state->setOriginalResponse($response);
        $state->setCurrentStep(NodeState::QUERY_OBTAINED);

        return new QueryObtainedEvent;
    }
}
