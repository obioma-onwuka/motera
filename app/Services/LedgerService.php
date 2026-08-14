<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class LedgerService
{
    /**
     * Contract: callers must hold a database transaction and must have
     * locked the account rows they move money on (lockForUpdate on the
     * SELECT). Amounts are passed as strings to preserve decimal precision.
     */

    /**
     * Hold funds: reduce the available balance only (ledger untouched).
     */
    public function hold(BankAccount $account, string $amount): void
    {
        $account->decrement('available_balance', $amount);
    }

    /**
     * Release a hold: restore the available balance only.
     */
    public function release(BankAccount $account, string $amount): void
    {
        $account->increment('available_balance', $amount);
    }

    /**
     * Post a debit leg: reduce ledger (and available, unless settling an
     * existing hold) and record the entry with the post-movement balance.
     */
    public function debit(Transaction $transaction, BankAccount $account, string $amount, string $description, bool $settleAvailable = true): LedgerEntry
    {
        $account->refresh();
        $account->decrement('ledger_balance', $amount);

        if ($settleAvailable) {
            $account->decrement('available_balance', $amount);
        }

        return $this->record($transaction, $account, 'debit', $amount, $description);
    }

    /**
     * Post a credit leg: increase ledger (and available) and record the
     * entry with the post-movement balance.
     */
    public function credit(Transaction $transaction, BankAccount $account, string $amount, string $description, bool $settleAvailable = true): LedgerEntry
    {
        $account->refresh();
        $account->increment('ledger_balance', $amount);

        if ($settleAvailable) {
            $account->increment('available_balance', $amount);
        }

        return $this->record($transaction, $account, 'credit', $amount, $description);
    }

    /**
     * Post a contra (system) leg so a single-legged transaction balances
     * to zero. The entry has no bank account and no balance_after.
     */
    public function contra(Transaction $transaction, string $type, string $amount, string $description): LedgerEntry
    {
        $entry = LedgerEntry::create([
            'transaction_id' => $transaction->id,
            'bank_account_id' => null,
            'reference' => $transaction->reference,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
        ]);

        activity()
            ->performedOn($transaction)
            ->when(Auth::check(), fn ($activity) => $activity->causedBy(Auth::user()))
            ->withProperties([
                'entry_id' => $entry->id,
                'type' => $type,
                'amount' => $amount,
                'contra' => true,
            ])
            ->log($description);

        return $entry;
    }

    /**
     * Record a customer-side ledger entry with the current post-movement
     * available balance as balance_after.
     */
    protected function record(Transaction $transaction, BankAccount $account, string $type, string $amount, string $description): LedgerEntry
    {
        $entry = LedgerEntry::create([
            'transaction_id' => $transaction->id,
            'bank_account_id' => $account->id,
            'reference' => $transaction->reference,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $account->available_balance,
            'description' => $description,
        ]);

        activity()
            ->performedOn($transaction)
            ->when(Auth::check(), fn ($activity) => $activity->causedBy(Auth::user()))
            ->withProperties([
                'entry_id' => $entry->id,
                'account_id' => $account->id,
                'type' => $type,
                'amount' => $amount,
            ])
            ->log($description);

        return $entry;
    }
}
