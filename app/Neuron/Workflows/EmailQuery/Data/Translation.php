<?php

declare(strict_types=1);

namespace App\Neuron\Workflows\EmailQuery\Data;

use App\Neuron\Workflows\EmailQuery\Helper\Language;
use UnexpectedValueException;

final readonly class Translation
{
    public function __construct(
        public Language $language,
        public string $text,
    ) {
        if (trim($text) === '') {
            throw new UnexpectedValueException('Translation text cannot be empty.');
        }
    }
}
