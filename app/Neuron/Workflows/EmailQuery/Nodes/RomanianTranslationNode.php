<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Models\Language;
use App\Neuron\Workflows\EmailQuery\Events\QueryObtainedEvent;
use App\Neuron\Workflows\EmailQuery\Events\TranslatingRequestsIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class DelegatorNode extends Node
{


    public function __invoke(QueryObtainedEvent $event, WorkflowState $state): TranslatingRequestsIssuedEvent
    {
        $state->set(NodeInterface::CURRENT_STEP, NodeState::DELEGATING);

        return new TranslatingRequestsIssuedEvent($this->computeBranches());
    }

    private function computeBranches(): array {
        return Language::all()->pluck('event_path', 'code')->toArray();
    }
}
