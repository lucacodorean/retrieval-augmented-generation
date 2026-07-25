<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Events;

use App\Neuron\Workflows\EmailQuery\Data\Translation;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use LogicException;
use NeuronAI\Workflow\Events\StopEvent;

abstract class TranslationRequestDoneEvent extends StopEvent implements EventInterface
{
    public function __construct(
        Translation $translation,
    ) {
        parent::__construct($translation);
    }

    public function getResult(): Translation
    {
        $result = parent::getResult();

        if (! $result instanceof Translation) {
            throw new LogicException('Translation event result must be a Translation.');
        }

        return $result;
    }

    public function getResultingState(): NodeState
    {
        return NodeState::QUERY_RESPONSE_TRANSLATED;
    }
}
