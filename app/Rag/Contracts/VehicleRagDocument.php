<?php

declare(strict_types=1);

namespace App\Rag\Contracts;

use App\Models\Vehicle;
use App\Models\VehicleDetails;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use NeuronAI\RAG\Document;

class VehicleRagDocument implements DocumentTransformer
{
    public static function build(Model $model): Document
    {
        /** @var Vehicle $model */
        if (! $model->relationLoaded('vehicleDetails')) {
            throw new LogicException('Vehicle details relationship must be loaded.');
        }

        /** @var VehicleDetails $details */
        $details = $model->vehicleDetails;
        if ($details === null) {
            throw new LogicException('Vehicle details relationship must be loaded.');
        }

        $document = new Document(sprintf(
            'Vehicle %s with VIN %s is a %s %s with %d hp and %s fuel.',
            $model->index,
            $model->vin,
            $details->brand->value,
            $details->model,
            $details->hp,
            $details->fuel->value,
        ));

        $document->id = $model->documentKey();
        $document->sourceType = Vehicle::class;
        $document->sourceName = $model->documentKey();

        $document
            ->addMetadata('vehicle_id', (int) $model->getKey())
            ->addMetadata('user_id', (int) $model->user_id)
            ->addMetadata('vehicle_details_id', (int) $model->vehicle_details_id);

        return $document
            ->addMetadata('vin', $model->vin)
            ->addMetadata('brand', $details->brand->value)
            ->addMetadata('model', $details->model)
            ->addMetadata('fuel', $details->fuel->value)
            ->addMetadata('hp', (int) $details->hp);
    }
}
