<?php

declare(strict_types=1);

namespace Tests\Unit\Rag;

use App\Models\Vehicle;
use App\Neuron\Resources\VehicleResource;
use App\Rag\Contracts\RagSearchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RagSearchableTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_is_searchable_and_exposes_its_result_resource(): void
    {
        $vehicle = Vehicle::factory()->create();

        $records = Vehicle::loadRagRecords([$vehicle->getKey()]);

        $this->assertInstanceOf(RagSearchable::class, $vehicle);
        $this->assertSame('vehicle-documents', Vehicle::ragCollection());
        $this->assertTrue($records->has($vehicle->getKey()));
        $this->assertTrue($records[$vehicle->getKey()]->relationLoaded('vehicleDetails'));
        $this->assertSame(VehicleResource::class, Vehicle::ragResultResource());
    }
}
