<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Workflows\EmailQuery\Events\TranslatingRequestsIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Events\TranslationRequestDoneEvent;
use App\Neuron\Workflows\EmailQuery\Helper\Languages;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class RomanianTranslationNode extends Node
{

    public function __invoke(TranslatingRequestsIssuedEvent $event, WorkflowState $state): TranslationRequestDoneEvent
    {
        $state->set(NodeInterface::CURRENT_STEP, NodeState::QUERY_RESPONSE_TRANSLATING);

        return new TranslationRequestDoneEvent($this->getTranslationLanguage());
    }

    public function getTranslationLanguage(): string {
        return Languages::ROMANIAN->value;
    }
}
