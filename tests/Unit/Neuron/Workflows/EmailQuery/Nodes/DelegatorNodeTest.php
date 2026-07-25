<?php

declare(strict_types=1);

namespace Tests\Unit\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Events\QueryObtainedEvent;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use App\Neuron\Workflows\EmailQuery\Nodes\DelegatorNode;
use ReflectionMethod;
use Tests\TestCase;

class DelegatorNodeTest extends TestCase
{
    public function test_it_delegates_using_the_email_query_workflow_state_contract(): void
    {
        $stateParameter = (new ReflectionMethod(DelegatorNode::class, '__invoke'))->getParameters()[1];

        $this->assertSame(EmailQueryWorkflowState::class, (string) $stateParameter->getType());

        $state = new EmailQueryWorkflowState;
        (new DelegatorNode)(new QueryObtainedEvent, $state);

        $this->assertSame(NodeState::DELEGATING, $state->currentStep());
    }
}
