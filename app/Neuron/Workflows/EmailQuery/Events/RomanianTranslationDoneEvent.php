<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Events;

use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Events\Event;

class QueryObtainedEvent implements Event, EventInterface
{
    public function getResultingState(): NodeState
    {
        return NodeState::QUERY_OBTAINED;
    }
}
