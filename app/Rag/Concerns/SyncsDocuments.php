<?php

declare(strict_types=1);

namespace App\Rag\Concerns;

use App\Observers\DocumentObserver;

trait SyncsDocuments
{
    public static function bootSyncsDocuments(): void
    {
        static::whenBooted(fn () => static::observe(DocumentObserver::class));
    }
}
