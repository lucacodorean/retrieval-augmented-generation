<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Events;

use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Events\Event;

class QueryObtainedEvent implements Event, EventInterface
{
    /**
     * Add class properties to carry custom data.
     */
    public function __construct(protected NodeState $receivingState = NodeState::START) {

    }

    public function getResultingState(): NodeState
    {
        return NodeState::QUERY_OBTAINED;
    }
}
