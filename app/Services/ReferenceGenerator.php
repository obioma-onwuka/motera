<?php

namespace App\Services;

use Illuminate\Support\Str;

class ReferenceGenerator
{
    /**
     * Generate a unique reference for a transaction.
     * Format: MTR-{TYPE}-{DATE}-{RANDOM}
     *
     * Pass an $existsCheck closure to guarantee uniqueness against the
     * relevant table's unique reference column.
     */
    public static function generate(string $type = 'TRF', ?\Closure $existsCheck = null, int $tries = 5): string
    {
        $date = now()->format('Ymd');

        for ($attempt = 0; $attempt < $tries; $attempt++) {
            $reference = "MTR-{$type}-{$date}-".strtoupper(Str::random(6));

            if ($existsCheck === null || ! $existsCheck($reference)) {
                return $reference;
            }
        }

        throw new \RuntimeException("Unable to generate a unique reference after {$tries} attempts.");
    }
}
