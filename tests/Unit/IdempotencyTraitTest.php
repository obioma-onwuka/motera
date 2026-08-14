<?php

use App\Exceptions\IdempotencyViolationException;
use App\Traits\InteractsWithIdempotency;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

beforeEach(function () {
    Cache::flush();
});

/**
 * Tiny anonymous stand-in for a class that relies on the idempotency trait,
 * exposing a public wrapper around the trait's protected idempotent() method.
 */
function makeIdempotentWorker(): object
{
    return new class
    {
        use InteractsWithIdempotency;

        public function run(string $key, callable $callback, int $ttl = 3600)
        {
            return $this->idempotent($key, $callback, $ttl);
        }
    };
}

it('executes the callback only once for a given key', function () {
    $worker = makeIdempotentWorker();
    $runs = 0;

    $result = $worker->run('transfer-abc', function () use (&$runs) {
        $runs++;

        return 'done';
    });

    expect($result)->toBe('done')
        ->and($runs)->toBe(1);

    expect(fn () => $worker->run('transfer-abc', fn () => $runs++))
        ->toThrow(IdempotencyViolationException::class);

    expect($runs)->toBe(1);
});

it('clears the marker when the callback fails so the operation can be retried', function () {
    $worker = makeIdempotentWorker();

    expect(fn () => $worker->run('deposit-xyz', fn () => throw new RuntimeException('boom')))
        ->toThrow(RuntimeException::class);

    $result = $worker->run('deposit-xyz', fn () => 'recovered');

    expect($result)->toBe('recovered');
});

it('treats different keys independently', function () {
    $worker = makeIdempotentWorker();
    $runs = 0;

    $worker->run('key-a', function () use (&$runs) {
        $runs++;
    });

    $worker->run('key-b', function () use (&$runs) {
        $runs++;
    });

    expect($runs)->toBe(2);
});
