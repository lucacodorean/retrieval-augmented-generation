<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\UpsertRagDocument;
use App\Models\Vehicle;
use Illuminate\Console\Command;

class BackfillRagDocuments extends Command
{
    protected $signature = 'rag:backfill-vehicles';

    protected $description = 'Queue RAG document upserts for all vehicles';

    public function handle(): int
    {
        $count = 0;

        foreach (Vehicle::query()->select('id')->cursor() as $vehicle) {
            UpsertRagDocument::dispatch(Vehicle::class, $vehicle->getKey());
            $count++;
        }

        $this->info("Queued {$count} vehicle documents.");

        return self::SUCCESS;
    }
}
