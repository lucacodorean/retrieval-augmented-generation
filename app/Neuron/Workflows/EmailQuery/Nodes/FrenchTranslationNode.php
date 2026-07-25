<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Agents\FrenchTranslationAgent;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Events\FrenchTranslationDoneEvent;
use App\Neuron\Workflows\EmailQuery\Events\FrenchTranslationRequestIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Node;

class FrenchTranslationNode extends Node
{
    public function __construct(
        private readonly FrenchTranslationAgent $agent,
    ) {}

    public function __invoke(FrenchTranslationRequestIssuedEvent $event, EmailQueryWorkflowState $state): FrenchTranslationDoneEvent
    {
        $state->setCurrentStep(NodeState::QUERY_RESPONSE_TRANSLATING);

        return new FrenchTranslationDoneEvent($this->agent->translate($state->sourceText()));
    }
}
