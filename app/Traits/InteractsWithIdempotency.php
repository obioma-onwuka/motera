<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait InteractsWithIdempotency
{
    /**
     * Ensure the operation is only executed once for the given key.
     */
    protected function idempotent(string $key, callable $callback, int $ttl = 3600)
    {
        $cacheKey = "idempotency:{$key}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $result = $callback();

        Cache::put($cacheKey, $result, $ttl);

        return $result;
    }
}
