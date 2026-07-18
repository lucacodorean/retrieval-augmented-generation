<?php

declare(strict_types=1);

namespace App\Enum;

enum Fuel: string
{
    case Diesel = 'diesel';
    case Gas = 'gas';
    case Electric = 'electric';
    case Hybrid = 'hybrid';
}
