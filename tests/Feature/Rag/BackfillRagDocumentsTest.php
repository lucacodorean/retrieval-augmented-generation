<?php

declare(strict_types=1);

namespace Tests\Feature\Rag;

use App\Jobs\UpsertRagDocument;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class BackfillRagDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_an_upsert_for_each_vehicle(): void
    {
        $vehicles = Vehicle::factory()->count(3)->create();
        Bus::fake();

        $this->artisan('rag:backfill-vehicles')
            ->expectsOutput('Queued 3 vehicle documents.')
            ->assertSuccessful();

        Bus::assertDispatched(UpsertRagDocument::class, 3);
        foreach ($vehicles as $vehicle) {
            Bus::assertDispatched(UpsertRagDocument::class, function (UpsertRagDocument $job) use ($vehicle): bool {
                return $job->modelClass === Vehicle::class && $job->modelId === $vehicle->getKey();
            });
        }
    }
}
