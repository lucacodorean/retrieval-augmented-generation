<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Workflows\EmailQuery\Events\FrenchTranslationRequestIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Events\QueryObtainedEvent;
use App\Neuron\Workflows\EmailQuery\Events\RomanianTranslationRequestIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Helper\Language;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Events\ParallelEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class DelegatorNode extends Node
{
    public function __invoke(QueryObtainedEvent $event, WorkflowState $state): ParallelEvent
    {
        $state->set(NodeInterface::CURRENT_STEP, NodeState::DELEGATING);

        return new ParallelEvent([
            Language::ROMANIAN->value => new RomanianTranslationRequestIssuedEvent(),
            Language::FRENCH->value   => new FrenchTranslationRequestIssuedEvent(),
        ]);
    }
}
