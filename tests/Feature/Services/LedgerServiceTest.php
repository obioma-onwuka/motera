<?php

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;

/**
 * Run a ledger operation against a locked account row inside a database
 * transaction, as required by the LedgerService contract.
 */
function withLockedAccount(User $user, BankAccount $account, callable $callback): void
{
    DB::transaction(function () use ($user, $account, $callback) {
        $locked = BankAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'bank_account_id' => $account->id,
        ]);

        $callback($locked, app(LedgerService::class), $transaction);
    });
}

it('holds and releases available balance without touching the ledger', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->withBalance(1000)->create();

    withLockedAccount($user, $account, function (BankAccount $locked, LedgerService $service) {
        $service->hold($locked, '250');

        expect((float) $locked->fresh()->available_balance)->toBe(750.0)
            ->and((float) $locked->fresh()->ledger_balance)->toBe(1000.0);

        $service->release($locked, '250');

        expect((float) $locked->fresh()->available_balance)->toBe(1000.0)
            ->and((float) $locked->fresh()->ledger_balance)->toBe(1000.0);
    });
});

it('debits both balances and records the post-movement balance', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->withBalance(1000)->create();

    withLockedAccount($user, $account, function (BankAccount $locked, LedgerService $service, Transaction $transaction) {
        $entry = $service->debit($transaction, $locked, '200', 'Test debit');

        expect($entry->type)->toBe('debit')
            ->and((float) $entry->amount)->toBe(200.0)
            ->and((float) $entry->balance_after)->toBe(800.0)
            ->and($entry->bank_account_id)->toBe($locked->id)
            ->and((float) $locked->fresh()->available_balance)->toBe(800.0)
            ->and((float) $locked->fresh()->ledger_balance)->toBe(800.0);
    });
});

it('debits without settling available balance when a hold already exists', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->withBalance(1000)->create();

    withLockedAccount($user, $account, function (BankAccount $locked, LedgerService $service, Transaction $transaction) {
        $service->hold($locked, '300');

        $entry = $service->debit($transaction, $locked, '300', 'Settle existing hold', settleAvailable: false);

        expect((float) $entry->balance_after)->toBe(700.0)
            ->and((float) $locked->fresh()->available_balance)->toBe(700.0)
            ->and((float) $locked->fresh()->ledger_balance)->toBe(700.0);
    });
});

it('credits both balances and records the post-movement balance', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->withBalance(1000)->create();

    withLockedAccount($user, $account, function (BankAccount $locked, LedgerService $service, Transaction $transaction) {
        $entry = $service->credit($transaction, $locked, '500', 'Test credit');

        expect($entry->type)->toBe('credit')
            ->and((float) $entry->balance_after)->toBe(1500.0)
            ->and($entry->bank_account_id)->toBe($locked->id)
            ->and((float) $locked->fresh()->available_balance)->toBe(1500.0)
            ->and((float) $locked->fresh()->ledger_balance)->toBe(1500.0);
    });
});

it('credits the ledger only when settleAvailable is false', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->withBalance(1000)->create();

    withLockedAccount($user, $account, function (BankAccount $locked, LedgerService $service, Transaction $transaction) {
        $entry = $service->credit($transaction, $locked, '500', 'Ledger-only credit', settleAvailable: false);

        expect((float) $entry->balance_after)->toBe(1000.0)
            ->and((float) $locked->fresh()->available_balance)->toBe(1000.0)
            ->and((float) $locked->fresh()->ledger_balance)->toBe(1500.0);
    });
});

it('records contra entries with no bank account and the transaction reference', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->withBalance(1000)->create();

    withLockedAccount($user, $account, function (BankAccount $locked, LedgerService $service, Transaction $transaction) {
        $entry = $service->contra($transaction, 'credit', '100', 'System contra');

        expect($entry->bank_account_id)->toBeNull()
            ->and($entry->reference)->toBe($transaction->reference)
            ->and($entry->type)->toBe('credit')
            ->and((float) $entry->amount)->toBe(100.0)
            ->and($entry->balance_after)->toBeNull();
    });
});
