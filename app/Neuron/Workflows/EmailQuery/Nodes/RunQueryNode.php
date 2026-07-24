<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Workflows\EmailQuery\Events\QueryObtainedEvent;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class RunQueryNode extends Node implements NodeInterface
{
    public function __invoke(StartEvent $event, WorkflowState $state): QueryObtainedEvent
    {
        $state->set(NodeInterface::CURRENT_STEP, NodeState::QUERY_IN_PROGRESS);
        return new QueryObtainedEvent();
    }
}
