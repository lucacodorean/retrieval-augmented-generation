<?php

declare(strict_types=1);

namespace Tests\Unit\Neuron\Workflows\EmailQuery\Nodes;

use App\Neuron\Workflows\EmailQuery\Data\Translation;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Events\EmailSendRequestEvent;
use App\Neuron\Workflows\EmailQuery\Helper\Language;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use App\Neuron\Workflows\EmailQuery\Nodes\CollectorNode;
use LogicException;
use NeuronAI\Workflow\Events\ParallelEvent;
use Tests\TestCase;

class CollectorNodeTest extends TestCase
{
    public function test_it_collects_translation_results_without_changing_the_original_response(): void
    {
        $response = $this->response();
        $state = new EmailQueryWorkflowState;
        $state->setOriginalResponse($response);
        $romanian = new Translation(Language::ROMANIAN, 'Doua vehicule corespund.');
        $french = new Translation(Language::FRENCH, 'Deux vehicules correspondent.');
        $parallelEvent = (new ParallelEvent([]))
            ->setResult('romanian', $romanian)
            ->setResult('french', $french);

        $event = (new CollectorNode)($parallelEvent, $state);

        $this->assertInstanceOf(EmailSendRequestEvent::class, $event);
        $this->assertSame(
            [
                Language::ROMANIAN->value => $romanian,
                Language::FRENCH->value => $french,
            ],
            $state->translations(),
        );
        $this->assertSame(NodeState::COLLECTED_TRANSLATIONS, $state->currentStep());
        $this->assertSame($response, $state->originalResponse());
        $this->assertSame(
            $response['response']['serialized'],
            $state->originalResponse()['response']['serialized'],
        );
    }

    public function test_it_rejects_a_non_translation_result_without_marking_collection_complete(): void
    {
        $state = new EmailQueryWorkflowState;
        $state->setOriginalResponse($this->response());
        $state->setTranslations([
            Language::ROMANIAN->value => new Translation(Language::ROMANIAN, 'Stale Romanian translation.'),
            Language::FRENCH->value => new Translation(Language::FRENCH, 'Stale French translation.'),
        ]);
        $parallelEvent = (new ParallelEvent([]))
            ->setResult('romanian', new Translation(Language::ROMANIAN, 'Doua vehicule corespund.'))
            ->setResult('invalid', 'not a translation');

        try {
            (new CollectorNode)($parallelEvent, $state);
            $this->fail('Expected a LogicException for a non-Translation result.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Collector result for branch [invalid] must be a Translation.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(NodeState::COLLECTING_TRANSLATIONS, $state->currentStep());
        $this->assertSame([], $state->translations());
        $this->assertNotSame(NodeState::COLLECTED_TRANSLATIONS, $state->currentStep());
    }

    public function test_it_rejects_a_collection_missing_a_required_language(): void
    {
        $state = new EmailQueryWorkflowState;
        $parallelEvent = (new ParallelEvent([]))
            ->setResult('romanian', new Translation(Language::ROMANIAN, 'Doua vehicule corespund.'));

        try {
            (new CollectorNode)($parallelEvent, $state);
            $this->fail('Expected a LogicException for a missing French translation.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Collector is missing a translation for language [fr].',
                $exception->getMessage(),
            );
        }

        $this->assertSame(NodeState::COLLECTING_TRANSLATIONS, $state->currentStep());
        $this->assertSame([], $state->translations());
    }

    public function test_it_rejects_duplicate_translation_languages(): void
    {
        $state = new EmailQueryWorkflowState;
        $parallelEvent = (new ParallelEvent([]))
            ->setResult('first-romanian', new Translation(Language::ROMANIAN, 'Prima traducere.'))
            ->setResult('second-romanian', new Translation(Language::ROMANIAN, 'A doua traducere.'));

        try {
            (new CollectorNode)($parallelEvent, $state);
            $this->fail('Expected a LogicException for duplicate Romanian translations.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Collector received duplicate translations for language [ro].',
                $exception->getMessage(),
            );
        }

        $this->assertSame(NodeState::COLLECTING_TRANSLATIONS, $state->currentStep());
        $this->assertSame([], $state->translations());
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
}
