<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery;

use App\Neuron\Workflows\EmailQuery\Middlewares\CollectorNodePushInformMiddleware;
use App\Neuron\Workflows\EmailQuery\Nodes\CollectorNode;
use App\Neuron\Workflows\EmailQuery\Nodes\DelegatorNode;
use App\Neuron\Workflows\EmailQuery\Nodes\FrenchTranslationNode;
use App\Neuron\Workflows\EmailQuery\Nodes\RomanianTranslationNode;
use App\Neuron\Workflows\EmailQuery\Nodes\RunQueryNode;
use NeuronAI\Workflow\Workflow;

class EmailQueryWorkflow extends Workflow
{
    protected function nodes(): array
    {
        return [
            new RunQueryNode(),
            new DelegatorNode(),

            new RomanianTranslationNode(),
            new FrenchTranslationNode(),

            new CollectorNode()
        ];
    }

    protected function middleware(): array {
        return [
            CollectorNode::class => new CollectorNodePushInformMiddleware(),
        ];
    }
}
