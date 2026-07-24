<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Workflows\EmailQuery\Events\FrenchTranslationDoneEvent;
use App\Neuron\Workflows\EmailQuery\Events\QueryObtainedEvent;
use App\Neuron\Workflows\EmailQuery\Events\RomanianTranslationDoneEvent;
use App\Neuron\Workflows\EmailQuery\Events\TranslatingRequestsIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Helper\Languages;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class CollectorNode extends Node
{
    public function __invoke(TranslatingRequestsIssuedEvent $event, WorkflowState $state): TranslatingRequestsIssuedEvent
    {
        $state->set(NodeInterface::CURRENT_STEP, NodeState::COLLECTING_TRANSLATIONS);

        return new TranslatingRequestsIssuedEvent([
            Languages::ROMANIAN->value => RomanianTranslationDoneEvent::class,
            Languages::FRENCH->value   => FrenchTranslationDoneEvent::class,
        ]);
    }
}
