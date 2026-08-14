<?php

namespace App\Services;

use App\Exceptions\InvalidPinException;
use App\Exceptions\TooManyPinAttemptsException;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class TransactionPinService
{
    /**
     * Maximum failed PIN attempts before lockout.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Lockout duration in seconds (15 minutes).
     */
    public const LOCKOUT_SECONDS = 900;

    /**
     * Throw if the user has not set a transaction PIN.
     */
    public function ensurePinSet(User $user): void
    {
        if (! $user->hasTransactionPin()) {
            throw new InvalidPinException('You have not set a transaction PIN yet.');
        }
    }

    /**
     * Verify the user's transaction PIN, tracking failed attempts per
     * context and locking the user out after too many failures.
     */
    public function verify(User $user, string $pin, string $context = 'transaction'): void
    {
        $this->ensurePinSet($user);

        $key = "pin:attempts:{$user->id}:{$context}";

        if (Cache::get($key, 0) >= self::MAX_ATTEMPTS) {
            throw new TooManyPinAttemptsException('Too many incorrect PIN attempts. Please try again in 15 minutes.');
        }

        if (! Hash::check($pin, $user->transaction_pin)) {
            Cache::add($key, 0, self::LOCKOUT_SECONDS);
            Cache::increment($key);

            throw new InvalidPinException('The transaction PIN you entered is incorrect.');
        }

        Cache::forget($key);
    }
}
