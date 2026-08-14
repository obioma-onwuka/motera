<?php

use App\Services\ReferenceGenerator;

it('generates references in the MTR-{TYPE}-{DATE}-{RANDOM} format', function () {
    $reference = ReferenceGenerator::generate('BILL');

    expect($reference)->toMatch('/^MTR-BILL-\d{8}-[A-Z0-9]{6}$/');
});

it('retries generation when the exists check reports a collision', function () {
    $calls = 0;

    $reference = ReferenceGenerator::generate('BILL', function (string $reference) use (&$calls) {
        $calls++;

        return $calls < 3;
    }, 5);

    expect($calls)->toBe(3)
        ->and($reference)->toMatch('/^MTR-BILL-\d{8}-[A-Z0-9]{6}$/');
});

it('throws a RuntimeException after exhausting the configured attempts', function () {
    expect(fn () => ReferenceGenerator::generate('BILL', fn () => true, 3))
        ->toThrow(RuntimeException::class);
});
