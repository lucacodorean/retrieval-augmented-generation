<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDetails;
use App\Util\VinGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'index' => $this->faker->regexify('[A-Z]{2}-[0-9]{3}-[A-Z]{2}'),
            'vin' => VinGenerator::generateVin(),
        ];
    }

    public function configure(): static
    {
        return $this
            ->for(User::factory(), 'owner')
            ->for(VehicleDetails::factory(), 'vehicleDetails');
    }
}
