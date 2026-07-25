<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Workflows\EmailQuery\Events\FrenchTranslationDoneEvent;
use App\Neuron\Workflows\EmailQuery\Events\FrenchTranslationRequestIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Events\TranslationRequestDoneEvent;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class FrenchTranslationNode extends Node
{
    public function __invoke(FrenchTranslationRequestIssuedEvent $event, WorkflowState $state): TranslationRequestDoneEvent
    {
        $state->set(NodeInterface::CURRENT_STEP, NodeState::QUERY_RESPONSE_TRANSLATING);

        return new FrenchTranslationDoneEvent();
    }
}
