<?php

declare(strict_types=1);

namespace App\Models;

use App\Neuron\Resources\VehicleResource;
use App\Rag\Concerns\SyncsDocuments;
use App\Rag\Contracts\Documentable;
use App\Rag\Contracts\RagSearchable;
use App\Rag\Documents\VehicleDocument;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['index', 'vin', 'user_id', 'vehicle_details_id'])]
class Vehicle extends Model implements Documentable, RagSearchable
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SyncsDocuments;

    public static function documentTransformer(): string
    {
        return VehicleDocument::class;
    }

    public static function documentRelations(): array
    {
        return ['vehicleDetails', 'owner'];
    }

    public function documentKey(): string
    {
        return 'vehicle:'.$this->getKey();
    }

    public static function ragCollection(): string
    {
        return 'vehicle-documents';
    }

    public static function loadRagRecords(array $ids): Collection
    {
        return static::query()
            ->with('vehicleDetails')
            ->whereKey($ids)
            ->get()
            ->keyBy(fn (Vehicle $vehicle): int => $vehicle->getKey());
    }

    public static function ragResultResource(): string
    {
        return VehicleResource::class;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehicleDetails(): BelongsTo
    {
        return $this->belongsTo(VehicleDetails::class);
    }
}
