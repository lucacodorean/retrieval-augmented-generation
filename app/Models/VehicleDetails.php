<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\Fuel;
use App\Enum\VehicleBrand;
use App\Observers\VehicleDetailsObserver;
use Database\Factories\VehicleDetailsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['brand', 'model', 'hp', 'fuel'])]
class VehicleDetails extends Model
{
    /** @use HasFactory<VehicleDetailsFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::observe(VehicleDetailsObserver::class);
    }

    protected function casts(): array
    {
        return [
            'brand' => VehicleBrand::class,
            'fuel' => Fuel::class,
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
