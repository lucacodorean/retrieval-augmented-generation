<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\DeleteRagDocument;
use App\Jobs\UpsertRagDocument;
use App\Models\Vehicle;
use App\Models\VehicleDetails;
use App\Observers\DocumentObserver;
use App\Observers\VehicleDetailsObserver;
use App\Rag\Concerns\SyncsDocuments;
use App\Rag\Contracts\Documentable;
use App\Rag\RagDocumentSynchronizer;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class RagDocumentSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_creation_queues_an_upsert_after_commit(): void
    {
        Bus::fake();

        $this->afterCommit(fn (): Vehicle => Vehicle::factory()->create());

        Bus::assertDispatched(UpsertRagDocument::class, function (UpsertRagDocument $job): bool {
            return $job->modelClass === Vehicle::class && is_int($job->modelId);
        });
    }

    public function test_vehicle_update_queues_an_upsert_after_commit(): void
    {
        Bus::fake();
        $vehicle = Vehicle::factory()->create();
        Bus::fake();

        $this->afterCommit(fn (): bool => $vehicle->update(['index' => 'ZZ-999-ZZ']));

        Bus::assertDispatched(UpsertRagDocument::class, function (UpsertRagDocument $job) use ($vehicle): bool {
            return $job->modelClass === Vehicle::class && $job->modelId === $vehicle->getKey();
        });
    }

    public function test_vehicle_deletion_queues_a_delete_after_commit(): void
    {
        Bus::fake();
        $vehicle = Vehicle::factory()->create();
        Bus::fake();

        $this->afterCommit(fn (): bool => $vehicle->delete());

        Bus::assertDispatched(DeleteRagDocument::class, function (DeleteRagDocument $job) use ($vehicle): bool {
            return $job->modelClass === Vehicle::class && $job->documentKey === 'vehicle:'.$vehicle->getKey();
        });
    }

    public function test_document_observers_handle_events_after_commit(): void
    {
        $this->assertInstanceOf(ShouldHandleEventsAfterCommit::class, new DocumentObserver);
        $this->assertInstanceOf(ShouldHandleEventsAfterCommit::class, new VehicleDetailsObserver);
    }

    public function test_document_jobs_are_queued(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new UpsertRagDocument(Vehicle::class, 1));
        $this->assertInstanceOf(ShouldQueue::class, new DeleteRagDocument(Vehicle::class, 'vehicle:1'));
    }

    public function test_upsert_job_reloads_the_documentable_model_with_its_document_relations(): void
    {
        Bus::fake();
        $vehicle = Vehicle::factory()->create();
        $synchronizer = Mockery::mock(RagDocumentSynchronizer::class);
        $synchronizer->shouldReceive('upsert')
            ->once()
            ->with(Mockery::on(function (Vehicle $model) use ($vehicle): bool {
                return $model->is($vehicle) && $model->relationLoaded('vehicleDetails');
            }));

        (new UpsertRagDocument(Vehicle::class, $vehicle->getKey()))->handle($synchronizer);
    }

    public function test_upsert_job_ignores_missing_and_non_documentable_models(): void
    {
        Bus::fake();
        $details = VehicleDetails::factory()->create();
        $synchronizer = Mockery::mock(RagDocumentSynchronizer::class);
        $synchronizer->shouldNotReceive('upsert');

        (new UpsertRagDocument(Vehicle::class, 999))->handle($synchronizer);
        (new UpsertRagDocument(VehicleDetails::class, $details->getKey()))->handle($synchronizer);
    }

    public function test_delete_job_delegates_to_the_synchronizer(): void
    {
        $synchronizer = Mockery::mock(RagDocumentSynchronizer::class);
        $synchronizer->shouldReceive('delete')->once()->with(Vehicle::class, 'vehicle:1');

        (new DeleteRagDocument(Vehicle::class, 'vehicle:1'))->handle($synchronizer);
    }

    public function test_shared_vehicle_details_update_queues_one_upsert_per_vehicle_and_none_for_details(): void
    {
        Bus::fake();
        $details = VehicleDetails::factory()->create();
        $vehicles = Vehicle::factory()->count(2)->for($details)->create();
        Bus::fake();

        $this->afterCommit(fn (): bool => $details->update(['hp' => 320]));

        Bus::assertDispatched(UpsertRagDocument::class, 2);
        foreach ($vehicles as $vehicle) {
            Bus::assertDispatched(UpsertRagDocument::class, function (UpsertRagDocument $job) use ($vehicle): bool {
                return $job->modelClass === Vehicle::class && $job->modelId === $vehicle->getKey();
            });
        }
        Bus::assertNotDispatched(UpsertRagDocument::class, function (UpsertRagDocument $job) use ($details): bool {
            return $job->modelClass === VehicleDetails::class && $job->modelId === $details->getKey();
        });
    }

    public function test_vehicle_details_creation_fans_out_one_upsert_per_related_vehicle(): void
    {
        Bus::fake();
        $details = VehicleDetails::factory()->create();
        $vehicles = Vehicle::factory()->count(2)->for($details)->create();
        Bus::fake();

        (new VehicleDetailsObserver)->created($details);

        Bus::assertDispatched(UpsertRagDocument::class, 2);
        foreach ($vehicles as $vehicle) {
            Bus::assertDispatched(UpsertRagDocument::class, function (UpsertRagDocument $job) use ($vehicle): bool {
                return $job->modelClass === Vehicle::class && $job->modelId === $vehicle->getKey();
            });
        }
    }

    public function test_vehicle_details_deletion_does_not_fan_out_document_jobs(): void
    {
        Bus::fake();
        $details = VehicleDetails::factory()->create();
        Vehicle::factory()->count(2)->for($details)->create();
        Bus::fake();

        $this->app['events']->dispatch('eloquent.deleted: '.VehicleDetails::class, $details);

        Bus::assertNothingDispatched();
    }

    public function test_vehicle_details_is_not_documentable_or_registered_for_document_syncing(): void
    {
        $details = new VehicleDetails;

        $this->assertNotInstanceOf(Documentable::class, $details);
        $this->assertNotContains(SyncsDocuments::class, class_uses_recursive(VehicleDetails::class));
    }

    private function afterCommit(callable $callback): mixed
    {
        $database = $this->app->make(DatabaseManager::class);

        $database->beginTransaction();
        $result = $callback();
        Bus::assertNothingDispatched();
        $database->commit();

        return $result;
    }
}
