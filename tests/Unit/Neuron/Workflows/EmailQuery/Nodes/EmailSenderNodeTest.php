<?php

declare(strict_types=1);

namespace Tests\Unit\Neuron\Workflows\EmailQuery\Nodes;

use App\Mail\VehicleAgentMail;
use App\Neuron\Workflows\EmailQuery\Data\Translation;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Events\EmailSendRequestEvent;
use App\Neuron\Workflows\EmailQuery\Helper\Language;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use App\Neuron\Workflows\EmailQuery\Nodes\EmailSenderNode;
use Illuminate\Support\Facades\Mail;
use Mockery;
use NeuronAI\Workflow\Events\StopEvent;
use RuntimeException;
use Tests\TestCase;

class EmailSenderNodeTest extends TestCase
{
    public function test_it_stays_in_sending_state_until_every_translation_is_sent(): void
    {
        $state = $this->stateReadyToSend();
        $sentLanguages = [];
        Mail::shouldReceive('to')
            ->twice()
            ->with('testreceiver@gmail.com')
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->twice()
            ->with(Mockery::type(VehicleAgentMail::class))
            ->andReturnUsing(function (VehicleAgentMail $mail) use ($state, &$sentLanguages): void {
                $this->assertSame(NodeState::SENDING_EMAIL, $state->currentStep());
                $sentLanguages[] = $mail->language;
            });

        $event = (new EmailSenderNode)(new EmailSendRequestEvent, $state);

        $this->assertInstanceOf(StopEvent::class, $event);
        $this->assertSame(['ROMANIAN', 'FRENCH'], $sentLanguages);
        $this->assertSame(NodeState::EMAILS_PUSHED, $state->currentStep());
    }

    public function test_a_later_send_failure_leaves_the_state_as_sending_and_propagates(): void
    {
        $state = $this->stateReadyToSend();
        $attempts = 0;
        $successfulSends = 0;
        $statesDuringAttempts = [];
        Mail::shouldReceive('to')
            ->twice()
            ->with('testreceiver@gmail.com')
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->twice()
            ->with(Mockery::type(VehicleAgentMail::class))
            ->andReturnUsing(function () use ($state, &$attempts, &$successfulSends, &$statesDuringAttempts): void {
                $attempts++;
                $statesDuringAttempts[] = $state->currentStep();

                if ($attempts === 2) {
                    throw new RuntimeException('Second delivery failed.');
                }

                $successfulSends++;
            });

        try {
            (new EmailSenderNode)(new EmailSendRequestEvent, $state);
            $this->fail('Expected the second delivery failure to propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Second delivery failed.', $exception->getMessage());
        }

        $this->assertSame(2, $attempts);
        $this->assertSame(1, $successfulSends);
        $this->assertSame([NodeState::SENDING_EMAIL, NodeState::SENDING_EMAIL], $statesDuringAttempts);
        $this->assertSame(NodeState::SENDING_EMAIL, $state->currentStep());
        $this->assertNotSame(NodeState::EMAILS_PUSHED, $state->currentStep());
    }

    private function stateReadyToSend(): EmailQueryWorkflowState
    {
        $state = new EmailQueryWorkflowState;
        $state->setOriginalResponse([
            'response' => [
                'natural-lang' => 'Two vehicles match.',
                'serialized' => [],
            ],
        ]);
        $state->setTranslations([
            Language::ROMANIAN->value => new Translation(Language::ROMANIAN, 'Doua vehicule corespund.'),
            Language::FRENCH->value => new Translation(Language::FRENCH, 'Deux vehicules correspondent.'),
        ]);

        return $state;
    }
}
