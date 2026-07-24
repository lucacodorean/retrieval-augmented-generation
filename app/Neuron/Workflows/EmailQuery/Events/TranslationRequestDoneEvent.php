<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Events;

use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Events\Event;
use NeuronAI\Workflow\Events\ParallelEvent;
use NeuronAI\Workflow\Events\StopEvent;

class TranslationRequestDoneEvent extends StopEvent implements EventInterface
{
    public function __construct(
        public readonly string $locale,
    ) {
        parent::__construct($locale);
    }

    public function getResultingState(): NodeState
    {
        return NodeState::QUERY_RESPONSE_TRANSLATED;
    }
}
