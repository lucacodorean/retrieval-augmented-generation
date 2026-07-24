<?php

declare(strict_types=1);

namespace App\Enum;

enum StepState: string
{
    case RUNNING_QUERY = "running_query";
    case DELEGATING = "delegating";
}
