<?php

declare(strict_types=1);

namespace App\Neuron\Agents;

use App\Neuron\Workflows\EmailQuery\Helper\Language;

class RomanianTranslationAgent extends TranslationAgent
{
    protected function language(): Language
    {
        return Language::ROMANIAN;
    }
}
