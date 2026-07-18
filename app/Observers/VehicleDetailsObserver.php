<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\UpsertRagDocument;
use App\Models\Vehicle;
use App\Models\VehicleDetails;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class VehicleDetailsObserver implements ShouldHandleEventsAfterCommit
{
    public function created(VehicleDetails $vehicleDetails): void
    {
        $this->upsertVehicles($vehicleDetails);
    }

    public function updated(VehicleDetails $vehicleDetails): void
    {
        $this->upsertVehicles($vehicleDetails);
    }

    private function upsertVehicles(VehicleDetails $vehicleDetails): void
    {
        foreach ($vehicleDetails->vehicles()->pluck('id') as $vehicleId) {
            UpsertRagDocument::dispatch(Vehicle::class, $vehicleId);
        }
    }
}
