<?php

declare(strict_types=1);

namespace Tests\Unit\Neuron\Workflows\EmailQuery;

use App\Neuron\Workflows\EmailQuery\Data\Translation;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflowState;
use App\Neuron\Workflows\EmailQuery\Helper\Language;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use UnexpectedValueException;

class EmailQueryWorkflowStateTest extends TestCase
{
    public function test_it_exposes_the_original_response_and_collected_translations(): void
    {
        $response = [
            'response' => [
                'natural-lang' => 'Two vehicles match.',
                'serialized' => [
                    ['record' => ['type' => 'vehicle', 'id' => 42], 'score' => 0.91],
                ],
            ],
        ];
        $translations = [
            Language::ROMANIAN->value => new Translation(
                Language::ROMANIAN,
                'Doua vehicule corespund.',
            ),
            Language::FRENCH->value => new Translation(
                Language::FRENCH,
                'Deux vehicules correspondent.',
            ),
        ];
        $state = new EmailQueryWorkflowState;

        $state->setOriginalResponse($response);
        $state->setCurrentStep(NodeState::QUERY_OBTAINED);
        $state->setTranslations($translations);

        $this->assertSame($response, $state->originalResponse());
        $this->assertSame('Two vehicles match.', $state->sourceText());
        $this->assertSame(NodeState::QUERY_OBTAINED, $state->currentStep());
        $this->assertSame($translations, $state->translations());
        $this->assertSame(
            $response['response']['serialized'],
            $state->originalResponse()['response']['serialized'],
        );
    }

    public function test_it_rejects_access_to_an_original_response_before_one_is_set(): void
    {
        $state = new EmailQueryWorkflowState;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Original response has not been set.');

        $state->originalResponse();
    }

    #[DataProvider('blankTranslations')]
    public function test_a_translation_rejects_blank_text(string $text): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Translation text cannot be empty.');

        new Translation(Language::FRENCH, $text);
    }

    /** @return array<string, array{string}> */
    public static function blankTranslations(): array
    {
        return [
            'empty' => [''],
            'whitespace' => [" \t\n"],
        ];
    }
}
