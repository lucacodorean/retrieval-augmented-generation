<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Rag\RagDocumentSynchronizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteRagDocument implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(
        public string $modelClass,
        public string $documentKey,
    ) {}

    public function handle(RagDocumentSynchronizer $synchronizer): void
    {
        $synchronizer->delete($this->modelClass, $this->documentKey);
    }
}
