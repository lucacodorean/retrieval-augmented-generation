<?php

declare(strict_types=1);

namespace App\Neuron\Resources;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;

class VehicleResource implements RagResultResource
{
    public function toArray(Model $model): array
    {
        /** @var Vehicle $model */
        $details = $model->vehicleDetails;

        return [
            'type' => 'vehicle',
            'id' => $model->getKey(),
            'attributes' => [
                'index' => $model->index,
                'vin' => $model->vin,
            ],
            'relationships' => [
                'vehicle_details' => [
                    'id' => $details->getKey(),
                    'brand' => $details->brand->value,
                    'model' => $details->model,
                    'hp' => $details->hp,
                    'fuel' => $details->fuel->value,
                ],
            ],
        ];
    }
}
