<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Nodes;

interface TranslationNode
{
    public function getTranslationLanguage(): string;
}
