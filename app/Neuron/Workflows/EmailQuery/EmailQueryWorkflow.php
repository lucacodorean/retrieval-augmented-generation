<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery;

use App\Neuron\Agents\FrenchTranslationAgent;
use App\Neuron\Agents\RomanianTranslationAgent;
use App\Neuron\Agents\VehicleAgent;
use App\Neuron\Workflows\EmailQuery\Nodes\CollectorNode;
use App\Neuron\Workflows\EmailQuery\Nodes\DelegatorNode;
use App\Neuron\Workflows\EmailQuery\Nodes\EmailSenderNode;
use App\Neuron\Workflows\EmailQuery\Nodes\FrenchTranslationNode;
use App\Neuron\Workflows\EmailQuery\Nodes\RomanianTranslationNode;
use App\Neuron\Workflows\EmailQuery\Nodes\RunQueryNode;
use NeuronAI\Exceptions\WorkflowException;
use NeuronAI\Workflow\Executor\AsyncExecutor;
use NeuronAI\Workflow\Persistence\PersistenceInterface;
use NeuronAI\Workflow\Workflow;

class EmailQueryWorkflow extends Workflow
{
    /**
     * @throws WorkflowException
     */
    public function __construct(
        private readonly string $query,
        private readonly VehicleAgent $vehicleAgent,
        private readonly RomanianTranslationAgent $romanianAgent,
        private readonly FrenchTranslationAgent $frenchAgent,
        ?PersistenceInterface $persistence = null,
        ?string $resumeToken = null,
        ?EmailQueryWorkflowState $state = null,
    ) {
        parent::__construct($persistence, $resumeToken, $state ?? new EmailQueryWorkflowState);

        /*
         * This method ensures that the branches are run asynchronous in order to facilitate
         * concurrency between branches.
        */
        $this->setExecutor(new AsyncExecutor);
    }

    protected function nodes(): array
    {
        return [
            new RunQueryNode($this->query, $this->vehicleAgent),
            new DelegatorNode,

            new RomanianTranslationNode($this->romanianAgent),
            new FrenchTranslationNode($this->frenchAgent),

            new CollectorNode,
            new EmailSenderNode,
        ];
    }
}
