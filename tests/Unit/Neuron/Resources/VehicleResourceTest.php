<?php

declare(strict_types=1);

namespace Tests\Unit\Neuron\Resources;

use App\Models\Vehicle;
use App\Neuron\Resources\VehicleResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_a_vehicle_to_its_rag_result_format(): void
    {
        $vehicle = Vehicle::factory()->create()->fresh(['vehicleDetails', 'owner']);

        $result = (new VehicleResource)->toArray($vehicle);

        $this->assertSame([
            'type' => 'vehicle',
            'id' => $vehicle->getKey(),
            'attributes' => [
                'index' => $vehicle->index,
                'vin' => $vehicle->vin,
            ],
            'relationships' => [
                'vehicle_details' => [
                    'id' => $vehicle->vehicleDetails->getKey(),
                    'brand' => $vehicle->vehicleDetails->brand->value,
                    'model' => $vehicle->vehicleDetails->model,
                    'hp' => $vehicle->vehicleDetails->hp,
                    'fuel' => $vehicle->vehicleDetails->fuel->value,
                ],
            ],
        ], $result);
        $this->assertArrayNotHasKey('user', $result['relationships']);
        $this->assertStringNotContainsString('owner', json_encode($result, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('name', json_encode($result, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('email', json_encode($result, JSON_THROW_ON_ERROR));
    }
}
