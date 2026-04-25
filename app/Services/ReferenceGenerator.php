<?php

namespace App\Services;

use Illuminate\Support\Str;

class ReferenceGenerator
{
    /**
     * Generate a unique reference for a transaction.
     * Format: MTR-{TYPE}-{DATE}-{RANDOM}
     */
    public static function generate(string $type = 'TRF'): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "MTR-{$type}-{$date}-{$random}";
    }
}
