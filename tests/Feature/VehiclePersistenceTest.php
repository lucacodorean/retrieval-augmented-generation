<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enum\Fuel;
use App\Enum\VehicleBrand;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiclePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_vehicles_can_share_details_in_both_relation_directions(): void
    {
        $details = VehicleDetails::factory()->create();
        $vehicles = Vehicle::factory()->count(2)->for($details)->create();

        $this->assertTrue($vehicles->every(
            fn (Vehicle $vehicle): bool => $vehicle->vehicleDetails->is($details),
        ));
        $this->assertCount(2, $details->vehicles);
        $this->assertTrue($details->vehicles->every(
            fn (Vehicle $vehicle): bool => $vehicles->contains($vehicle),
        ));
    }

    public function test_vehicle_details_attributes_are_cast_to_enums(): void
    {
        $details = VehicleDetails::factory()->create([
            'brand' => VehicleBrand::Nissan,
            'fuel' => Fuel::Electric,
        ]);
        $details->refresh();

        $this->assertSame(VehicleBrand::Nissan, $details->brand);
        $this->assertSame(Fuel::Electric, $details->fuel);
    }

    public function test_vehicle_factory_creates_related_details_and_distinct_vins(): void
    {
        $vehicles = Vehicle::factory()->count(2)->create();

        $this->assertTrue($vehicles->every(
            fn (Vehicle $vehicle): bool => $vehicle->vehicleDetails instanceof VehicleDetails,
        ));
        $this->assertTrue($vehicles->every(
            fn (Vehicle $vehicle): bool => $vehicle->owner instanceof User,
        ));
        $this->assertTrue($vehicles->every(
            fn (Vehicle $vehicle): bool => is_string($vehicle->index) && $vehicle->index !== '',
        ));
        $this->assertCount(2, $vehicles->pluck('vin')->unique());
        $this->assertTrue($vehicles->every(
            fn (Vehicle $vehicle): bool => strlen($vehicle->vin) === 17,
        ));
        $this->assertTrue($vehicles->every(
            fn (Vehicle $vehicle): bool => preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vehicle->vin) === 1,
        ));
    }

    public function test_database_seeder_shares_each_seeded_vehicle_details_record_with_two_vehicles(): void
    {
        $this->seed();

        $details = VehicleDetails::withCount('vehicles')->get();

        $this->assertSame(10, Vehicle::count());
        $this->assertCount(5, $details);
        $this->assertCount(5, Vehicle::query()->distinct()->pluck('vehicle_details_id'));
        $this->assertTrue($details->every(
            fn (VehicleDetails $vehicleDetails): bool => $vehicleDetails->vehicles_count === 2,
        ));
    }
}
