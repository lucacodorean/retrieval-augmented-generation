<?php

declare(strict_types=1);

namespace Tests\Unit\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Agents\FrenchTranslationAgent;
use App\Neuron\Agents\RomanianTranslationAgent;
use App\Neuron\Workflows\EmailQuery\Data\Translation;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Events\FrenchTranslationDoneEvent;
use App\Neuron\Workflows\EmailQuery\Events\FrenchTranslationRequestIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Events\RomanianTranslationDoneEvent;
use App\Neuron\Workflows\EmailQuery\Events\RomanianTranslationRequestIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Events\TranslationRequestDoneEvent;
use App\Neuron\Workflows\EmailQuery\Helper\Language;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use App\Neuron\Workflows\EmailQuery\Nodes\FrenchTranslationNode;
use App\Neuron\Workflows\EmailQuery\Nodes\RomanianTranslationNode;
use Mockery;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class TranslationNodeTest extends TestCase
{
    public function test_concrete_done_events_expose_typed_translation_results(): void
    {
        $this->assertTrue((new ReflectionClass(TranslationRequestDoneEvent::class))->isAbstract());

        $events = [
            new FrenchTranslationDoneEvent('Deux vehicules correspondent.'),
            new RomanianTranslationDoneEvent('Doua vehicule corespund.'),
        ];

        foreach ($events as $event) {
            $this->assertSame(
                Translation::class,
                (string) (new ReflectionMethod($event, 'getResult'))->getReturnType(),
            );
            $this->assertInstanceOf(Translation::class, $event->getResult());
        }
    }

    public function test_it_translates_only_the_source_text_into_french(): void
    {
        $response = $this->response();
        $state = $this->stateWithOriginalResponse($response);
        $agent = Mockery::mock(FrenchTranslationAgent::class);
        $agent->expects('translate')
            ->with('Two vehicles match.')
            ->andReturn('Deux vehicules correspondent.');

        $event = (new FrenchTranslationNode($agent))(
            new FrenchTranslationRequestIssuedEvent,
            $state,
        );

        $this->assertInstanceOf(FrenchTranslationDoneEvent::class, $event);
        $this->assertEquals(
            new Translation(Language::FRENCH, 'Deux vehicules correspondent.'),
            $event->getResult(),
        );
        $this->assertSame(Language::FRENCH, $event->getResult()->language);
        $this->assertSame('Deux vehicules correspondent.', $event->getResult()->text);
        $this->assertSame(NodeState::QUERY_RESPONSE_TRANSLATED, $event->getResultingState());
        $this->assertSame(NodeState::QUERY_RESPONSE_TRANSLATING, $state->currentStep());
        $this->assertSame([], $state->translations());
        $this->assertSame($response, $state->originalResponse());
        $this->assertSame($response['response']['serialized'], $state->originalResponse()['response']['serialized']);
    }

    public function test_it_translates_only_the_source_text_into_romanian(): void
    {
        $response = $this->response();
        $state = $this->stateWithOriginalResponse($response);
        $agent = Mockery::mock(RomanianTranslationAgent::class);
        $agent->expects('translate')
            ->with('Two vehicles match.')
            ->andReturn('Doua vehicule corespund.');

        $event = (new RomanianTranslationNode($agent))(
            new RomanianTranslationRequestIssuedEvent,
            $state,
        );

        $this->assertInstanceOf(RomanianTranslationDoneEvent::class, $event);
        $this->assertEquals(
            new Translation(Language::ROMANIAN, 'Doua vehicule corespund.'),
            $event->getResult(),
        );
        $this->assertSame(Language::ROMANIAN, $event->getResult()->language);
        $this->assertSame('Doua vehicule corespund.', $event->getResult()->text);
        $this->assertSame(NodeState::QUERY_RESPONSE_TRANSLATED, $event->getResultingState());
        $this->assertSame(NodeState::QUERY_RESPONSE_TRANSLATING, $state->currentStep());
        $this->assertSame([], $state->translations());
        $this->assertSame($response, $state->originalResponse());
        $this->assertSame($response['response']['serialized'], $state->originalResponse()['response']['serialized']);
    }

    /** @return array{response: array{natural-lang: string, serialized: list<array{record: array<string, mixed>, score: float}>}} */
    private function response(): array
    {
        return [
            'response' => [
                'natural-lang' => 'Two vehicles match.',
                'serialized' => [
                    ['record' => ['type' => 'vehicle', 'id' => 42], 'score' => 0.91],
                ],
            ],
        ];
    }

    /** @param array{response: array{natural-lang: string, serialized: list<array{record: array<string, mixed>, score: float}>}} $response */
    private function stateWithOriginalResponse(array $response): EmailQueryWorkflowState
    {
        $state = new EmailQueryWorkflowState;
        $state->setOriginalResponse($response);

        return $state;
    }
}
