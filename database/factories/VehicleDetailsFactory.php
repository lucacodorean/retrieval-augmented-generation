<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\Fuel;
use App\Enum\VehicleBrand;
use App\Models\VehicleDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleDetailsFactory extends Factory
{
    protected $model = VehicleDetails::class;

    public function definition(): array
    {
        return [
            'brand' => $this->faker->randomElement(VehicleBrand::cases()),
            'model' => $this->faker->word(),
            'fuel' => $this->faker->randomElement(Fuel::cases()),
            'hp' => $this->faker->numberBetween(90, 300),
        ];
    }
}
