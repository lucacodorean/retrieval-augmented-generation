<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Rag\Contracts\Documentable;
use App\Rag\RagDocumentSynchronizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;

class UpsertRagDocument implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(
        public string $modelClass,
        public int|string $modelId,
    ) {}

    public function handle(RagDocumentSynchronizer $synchronizer): void
    {
        if (! is_a($this->modelClass, Model::class, true) ||
            ! is_a($this->modelClass, Documentable::class, true)
        ) {
            return;
        }

        $model = $this->modelClass::query()
            ->with($this->modelClass::documentRelations())
            ->find($this->modelId);

        if (! $model instanceof Documentable) {
            return;
        }

        $synchronizer->upsert($model);
    }
}
