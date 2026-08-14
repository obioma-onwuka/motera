<?php

use App\Exceptions\InvalidPinException;
use App\Exceptions\TooManyPinAttemptsException;
use App\Models\User;
use App\Services\TransactionPinService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('accepts the correct pin', function () {
    $user = User::factory()->withPin('1234')->create();

    expect(fn () => app(TransactionPinService::class)->verify($user, '1234', 'bills'))
        ->not->toThrow(Throwable::class);
});

it('locks the user out after five failed attempts even with the correct pin', function () {
    $service = app(TransactionPinService::class);
    $user = User::factory()->withPin('1234')->create();

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        expect(fn () => $service->verify($user, '0000', 'bills'))
            ->toThrow(InvalidPinException::class);
    }

    expect(fn () => $service->verify($user, '1234', 'bills'))
        ->toThrow(TooManyPinAttemptsException::class);
});

it('throws InvalidPinException when the user has not set a pin', function () {
    $user = User::factory()->create();

    expect(fn () => app(TransactionPinService::class)->ensurePinSet($user))
        ->toThrow(InvalidPinException::class);
});

it('resets the failure counter after a successful verification', function () {
    $service = app(TransactionPinService::class);
    $user = User::factory()->withPin('1234')->create();

    expect(fn () => $service->verify($user, '0000', 'bills'))
        ->toThrow(InvalidPinException::class);

    $service->verify($user, '1234', 'bills');

    // Had the counter not reset, the fifth failure below would trigger a
    // lockout; four failures still throw InvalidPinException, proving the
    // counter started fresh.
    for ($attempt = 1; $attempt <= 4; $attempt++) {
        expect(fn () => $service->verify($user, '0000', 'bills'))
            ->toThrow(InvalidPinException::class);
    }

    expect(fn () => $service->verify($user, '1234', 'bills'))
        ->not->toThrow(Throwable::class);
});
