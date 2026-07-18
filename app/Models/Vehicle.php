<?php

declare(strict_types=1);

namespace App\Models;

use App\Rag\Concerns\SyncsDocuments;
use App\Rag\Contracts\Documentable;
use App\Rag\Contracts\VehicleRagDocument;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['index', 'vin', 'user_id', 'vehicle_details_id'])]
class Vehicle extends Model implements Documentable
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SyncsDocuments;

    public static function documentTransformer(): string
    {
        return VehicleRagDocument::class;
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehicleDetails(): BelongsTo
    {
        return $this->belongsTo(VehicleDetails::class);
    }
}
