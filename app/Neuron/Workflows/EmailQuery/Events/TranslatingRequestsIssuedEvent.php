<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Events;

use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Events\Event;
use NeuronAI\Workflow\Events\ParallelEvent;

class TranslatingRequestsIssuedEvent extends ParallelEvent implements Event, EventInterface
{
    public function __construct(public readonly array $branches) {
        parent::__construct($branches);
    }

    public function getResultingState(): NodeState
    {
        return NodeState::QUERY_RESPONSE_TRANSLATIONS_REQUESTED;
    }
}
