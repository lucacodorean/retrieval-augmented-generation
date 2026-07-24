<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

class FirstStep extends Node
{
    public function __invoke(StartEvent $event, WorkflowState $state): StopEvent
    {
        // ...

        return new StopEvent();
    }
}
