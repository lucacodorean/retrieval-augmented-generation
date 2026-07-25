<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

use App\Mail\VehicleAgentMail;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Events\EmailSendRequestEvent;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use Illuminate\Support\Facades\Mail;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Node;

class EmailSenderNode extends Node
{
    public function __invoke(EmailSendRequestEvent $event, EmailQueryWorkflowState $state): StopEvent
    {
        $state->setCurrentStep(NodeState::SENDING_EMAIL);

        foreach ($state->translations() as $translation) {
            Mail::to('testreceiver@gmail.com')->send(new VehicleAgentMail(
                $translation->text,
                $translation->language->name,
                $state->originalResponse()['response']['serialized']
            ));
        }

        $state->setCurrentStep(NodeState::EMAILS_PUSHED);

        return new StopEvent;
    }
}
