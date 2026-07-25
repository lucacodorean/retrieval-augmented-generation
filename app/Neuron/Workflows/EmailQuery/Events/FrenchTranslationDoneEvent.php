<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Events;

use App\Neuron\Workflows\EmailQuery\Helper\Language;

class FrenchTranslationDoneEvent extends TranslationRequestDoneEvent
{
    public function __construct()
    {
        parent::__construct(Language::FRENCH);
    }
}
