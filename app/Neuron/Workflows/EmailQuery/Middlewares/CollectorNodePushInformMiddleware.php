<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Middlewares;

use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use NeuronAI\Workflow\Events\Event;
use NeuronAI\Workflow\Middleware\WorkflowMiddleware;
use NeuronAI\Workflow\NodeInterface;
use App\Neuron\Workflows\EmailQuery\Nodes\NodeInterface as QueryEmailNodeInterface;
use NeuronAI\Workflow\WorkflowState;

class CollectorNodePushInformMiddleware implements WorkflowMiddleware
{
    /**
     * Execute before the node runs.
     *
     * This method is called before the node's __invoke method executes.
     * Use this for validation, logging, state preparation, etc.
     *
     * @param NodeInterface $node The node about to execute
     * @param Event $event The event being processed
     * @param WorkflowState $state The current workflow state
     */
    public function before(NodeInterface $node, Event $event, WorkflowState $state): void {
        logger("Attempting to push the translated results.");
    }

    /**
     * Execute after the node runs.
     *
     * This method is called after the node's __invoke method completes.
     * For streaming nodes that return Generators, this is called after
     * the generator is fully consumed and the final Event is available.
     *
     * @param NodeInterface $node The node that executed
     * @param Event $result The final result event returned by the node
     * @param WorkflowState $state The current workflow state
     */
    public function after(NodeInterface $node, Event $result, WorkflowState $state): void {
        $state->set(QueryEmailNodeInterface::CURRENT_STEP, NodeState::COLLECTED_TRANSLATIONS);
        logger("Translated results were pushed.");

    }
}
