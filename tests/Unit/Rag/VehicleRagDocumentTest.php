<?php

declare(strict_types=1);

namespace Tests\Unit\Rag;

use App\Enum\Fuel;
use App\Enum\VehicleBrand;
use App\Models\Vehicle;
use App\Models\VehicleDetails;
use App\Rag\Contracts\VehicleRagDocument;
use LogicException;
use Tests\TestCase;

class VehicleRagDocumentTest extends TestCase
{
    public function test_it_transforms_a_vehicle_with_loaded_details_into_a_document(): void
    {
        $vehicle = new Vehicle;
        $vehicle->setRawAttributes([
            'id' => 42,
            'index' => 'VEH-0001',
            'vin' => '1N4AZ1CP0KC300001',
            'user_id' => 9,
            'vehicle_details_id' => 12,
        ]);

        $details = new VehicleDetails;
        $details->setRawAttributes([
            'id' => 12,
            'brand' => VehicleBrand::Nissan->value,
            'model' => 'Leaf',
            'hp' => 150,
            'fuel' => Fuel::Electric->value,
        ]);
        $vehicle->setRelation('vehicleDetails', $details);

        $document = VehicleRagDocument::build($vehicle);

        $this->assertSame('vehicle:42', $document->getId());
        $this->assertSame(Vehicle::class, $document->sourceType);
        $this->assertSame('vehicle:42', $document->sourceName);
        $this->assertSame(
            'Vehicle VEH-0001 with VIN 1N4AZ1CP0KC300001 is a Nissan Leaf with 150 hp and electric fuel.',
            $document->getContent(),
        );
        $this->assertSame([
            'vehicle_id' => 42,
            'user_id' => 9,
            'vehicle_details_id' => 12,
            'vin' => '1N4AZ1CP0KC300001',
            'brand' => 'Nissan',
            'model' => 'Leaf',
            'fuel' => 'electric',
            'hp' => 150,
        ], $document->metadata);
    }

    public function test_it_requires_vehicle_details_to_be_loaded(): void
    {
        $vehicle = new Vehicle;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Vehicle details relationship must be loaded.');

        VehicleRagDocument::build($vehicle);
    }

    public function test_it_omits_owner_metadata(): void
    {
        $vehicle = new Vehicle;
        $vehicle->setRawAttributes([
            'id' => 42,
            'index' => 'VEH-0001',
            'vin' => '1N4AZ1CP0KC300001',
            'vehicle_details_id' => 12,
        ]);

        $details = new VehicleDetails;
        $details->setRawAttributes([
            'id' => 12,
            'brand' => VehicleBrand::Nissan->value,
            'model' => 'Leaf',
            'hp' => 150,
            'fuel' => Fuel::Electric->value,
        ]);
        $vehicle->setRelation('vehicleDetails', $details);

        $document = VehicleRagDocument::build($vehicle);

        $this->assertStringNotContainsString('owned by', $document->getContent());
    }

    public function test_it_rejects_a_null_loaded_vehicle_details_relation(): void
    {
        $vehicle = new Vehicle;
        $vehicle->setRelation('vehicleDetails', null);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Vehicle details relationship must be loaded.');

        VehicleRagDocument::build($vehicle);
    }
}
