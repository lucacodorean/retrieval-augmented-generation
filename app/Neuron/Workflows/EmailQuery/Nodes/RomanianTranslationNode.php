<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Workflows\EmailQuery\Events\RomanianTranslationDoneEvent;
use App\Neuron\Workflows\EmailQuery\Events\RomanianTranslationRequestIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Events\TranslationRequestDoneEvent;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class RomanianTranslationNode extends Node
{

    public function __invoke(RomanianTranslationRequestIssuedEvent $event, WorkflowState $state): TranslationRequestDoneEvent
    {
        $state->set(NodeInterface::CURRENT_STEP, NodeState::QUERY_RESPONSE_TRANSLATING);

        return new RomanianTranslationDoneEvent();
    }
}
