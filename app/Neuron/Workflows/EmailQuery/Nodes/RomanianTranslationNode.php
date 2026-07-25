<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Agents\RomanianTranslationAgent;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Events\RomanianTranslationDoneEvent;
use App\Neuron\Workflows\EmailQuery\Events\RomanianTranslationRequestIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Node;

class RomanianTranslationNode extends Node
{
    public function __construct(
        private readonly RomanianTranslationAgent $agent,
    ) {}

    public function __invoke(RomanianTranslationRequestIssuedEvent $event, EmailQueryWorkflowState $state): RomanianTranslationDoneEvent
    {
        $state->setCurrentStep(NodeState::QUERY_RESPONSE_TRANSLATING);

        return new RomanianTranslationDoneEvent($this->agent->translate($state->sourceText()));
    }
}
