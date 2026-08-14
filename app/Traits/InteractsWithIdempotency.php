<?php

namespace App\Traits;

use App\Exceptions\IdempotencyViolationException;
use Illuminate\Support\Facades\Cache;

trait InteractsWithIdempotency
{
    /**
     * Ensure the operation is only executed once for the given key.
     *
     * Cache::add is atomic, so concurrent requests with the same key
     * cannot both pass the check. Only a marker is stored (never the
     * callback result), and the marker is cleared on failure so the
     * user can retry.
     */
    protected function idempotent(string $key, callable $callback, int $ttl = 3600)
    {
        $cacheKey = "idempotency:{$key}";

        if (! Cache::add($cacheKey, true, $ttl)) {
            throw new IdempotencyViolationException('This request has already been submitted.');
        }

        try {
            return $callback();
        } catch (\Throwable $e) {
            Cache::forget($cacheKey);

            throw $e;
        }
    }
}
