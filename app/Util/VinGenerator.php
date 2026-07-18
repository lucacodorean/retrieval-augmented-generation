<?php

declare(strict_types=1);

namespace App\Util;

final class VinGenerator
{
    public static function generateVin(): string
    {
        $charSet = 'ABCDEFGHJKLMNPRSTUVWXYZ';
        $numSet = '1234567890';
        $fullSet = $charSet.$numSet;

        // WMI (1-3), VDS (4-8), VIS (10-17)
        $vin = '';
        for ($i = 0; $i < 17; $i++) {
            if ($i == 8) { // Position 9 is the Check Digit, placeholder for now
                $vin .= '0';
            } else {
                $vin .= $fullSet[rand(0, strlen($fullSet) - 1)];
            }
        }

        // Calculate Check Digit
        $weights = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];
        $charValues = [
            'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8,
            'J' => 1, 'K' => 2, 'L' => 3, 'M' => 4, 'N' => 5, 'P' => 7, 'R' => 9,
            'S' => 2, 'T' => 3, 'U' => 4, 'V' => 5, 'W' => 6, 'X' => 7, 'Y' => 8, 'Z' => 9,
            '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, '0' => 0,
        ];

        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $char = $vin[$i];
            $val = $charValues[$char] ?? 0;
            $sum += ($val * $weights[$i]);
        }

        $remainder = $sum % 11;
        $checkDigit = ($remainder == 10) ? 'X' : $remainder;

        // Inject Check Digit into position 9
        $vin[8] = (string) $checkDigit;

        return $vin;
    }
}
