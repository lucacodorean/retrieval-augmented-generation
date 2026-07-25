<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Events;

use App\Neuron\Workflows\EmailQuery\Data\Translation;
use App\Neuron\Workflows\EmailQuery\Helper\Language;

class RomanianTranslationDoneEvent extends TranslationRequestDoneEvent
{
    public function __construct(string $text)
    {
        parent::__construct(new Translation(Language::ROMANIAN, $text));
    }
}
