<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Events;

use App\Neuron\Workflows\EmailQuery\Helper\Language;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;

class RomanianTranslationDoneEvent extends TranslationRequestDoneEvent
{
    public function __construct()
    {
        parent::__construct(Language::ROMANIAN);
    }

    public function getResultingState(): NodeState
    {
        return NodeState::QUERY_RESPONSE_TRANSLATED;
    }
}
