<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDetails;
use App\Neuron\Workflows\EmailQuery\Events\FrenchTranslationDoneEvent;
use App\Neuron\Workflows\EmailQuery\Events\RomanianTranslationDoneEvent;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = User::factory(10)->create();
        $vehicleDetails = VehicleDetails::factory(5)->create();

        $vehicleDetails->each(function (VehicleDetails $details) use ($users): void {
            foreach (range(1, 2) as $_) {
                Vehicle::factory()
                    ->for($users->random(), 'owner')
                    ->for($details, 'vehicleDetails')
                    ->create();
            }
        });
    }
}
