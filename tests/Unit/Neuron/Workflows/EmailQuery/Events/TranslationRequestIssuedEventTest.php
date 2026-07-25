<?php

declare(strict_types=1);

namespace Tests\Unit\Neuron\Workflows\EmailQuery\Events;

use App\Neuron\Workflows\EmailQuery\Events\FrenchTranslationRequestIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Events\RomanianTranslationRequestIssuedEvent;
use App\Neuron\Workflows\EmailQuery\Helper\NodeState;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TranslationRequestIssuedEventTest extends TestCase
{
    /** @param class-string<FrenchTranslationRequestIssuedEvent|RomanianTranslationRequestIssuedEvent> $eventClass */
    #[DataProvider('requestEvents')]
    public function test_it_reports_that_translations_were_requested(string $eventClass): void
    {
        $this->assertSame(
            NodeState::QUERY_RESPONSE_TRANSLATIONS_REQUESTED,
            (new $eventClass)->getResultingState(),
        );
    }

    /** @return array<string, array{class-string<FrenchTranslationRequestIssuedEvent|RomanianTranslationRequestIssuedEvent>}> */
    public static function requestEvents(): array
    {
        return [
            'French' => [FrenchTranslationRequestIssuedEvent::class],
            'Romanian' => [RomanianTranslationRequestIssuedEvent::class],
        ];
    }
}
