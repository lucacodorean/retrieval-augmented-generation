<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery;

use App\Neuron\Workflows\EmailQuery\Middlewares\CollectorNodePushInformMiddleware;
use App\Neuron\Workflows\EmailQuery\Nodes\CollectorNode;
use App\Neuron\Workflows\EmailQuery\Nodes\DelegatorNode;
use App\Neuron\Workflows\EmailQuery\Nodes\FrenchTranslationNode;
use App\Neuron\Workflows\EmailQuery\Nodes\RomanianTranslationNode;
use App\Neuron\Workflows\EmailQuery\Nodes\RunQueryNode;
use NeuronAI\Exceptions\WorkflowException;
use NeuronAI\Workflow\Executor\AsyncExecutor;
use NeuronAI\Workflow\Persistence\PersistenceInterface;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;

class EmailQueryWorkflow extends Workflow
{

    /**
     * @throws WorkflowException
     */
    public function __construct(
        ?PersistenceInterface $persistence = null,
        ?string $resumeToken = null,
        protected ?WorkflowState $state = null,
    )
    {
        parent::__construct($persistence, $resumeToken, $this->state);

        /*
         * This method ensures that the branches are run asynchronous in order to facilitate
         * concurrency between branches.
        */
        $this->setExecutor(new AsyncExecutor());
    }

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

    protected function middleware(): array
    {
        return [
            CollectorNode::class => new CollectorNodePushInformMiddleware(),
        ];
    }
}
