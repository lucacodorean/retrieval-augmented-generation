<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Events;

use App\Neuron\Workflows\EmailQuery\Helper\NodeState;

interface EventInterface
{
    public function getResultingState(): NodeState;
}
