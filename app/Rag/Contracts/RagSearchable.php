<?php

declare(strict_types=1);

namespace App\Rag\Contracts;

use App\Neuron\Resources\RagResultResource;
use Illuminate\Database\Eloquent\Collection;

interface RagSearchable
{
    public static function ragCollection(): string;

    /** @param list<int> $ids */
    public static function loadRagRecords(array $ids): Collection;

    /** @return class-string<RagResultResource> */
    public static function ragResultResource(): string;
}
