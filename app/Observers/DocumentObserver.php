<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\DeleteRagDocument;
use App\Jobs\UpsertRagDocument;
use App\Rag\Contracts\Documentable;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

class DocumentObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Model $model): void
    {
        $this->upsert($model);
    }

    public function updated(Model $model): void
    {
        $this->upsert($model);
    }

    public function deleted(Model $model): void
    {
        if (! $model instanceof Documentable) {
            return;
        }

        DeleteRagDocument::dispatch($model::class, $model->documentKey());
    }

    private function upsert(Model $model): void
    {
        if (! $model instanceof Documentable) {
            return;
        }

        UpsertRagDocument::dispatch($model::class, $model->getKey());
    }
}
