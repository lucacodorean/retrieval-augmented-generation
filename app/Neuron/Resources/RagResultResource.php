<?php

declare(strict_types=1);

namespace App\Neuron\Resources;

use Illuminate\Database\Eloquent\Model;

interface RagResultResource
{
    /** @return array<string, mixed> */
    public function toArray(Model $model): array;
}
