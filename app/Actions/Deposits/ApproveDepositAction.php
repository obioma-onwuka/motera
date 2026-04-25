<?php

namespace App\Actions\Deposits;

use App\Actions\BaseAction;
use App\Models\DepositRequest;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use App\Enums\TransactionStatus;

class ApproveDepositAction extends BaseAction
{
    /**
     * Execute the deposit approval action.
     */
    public function execute(mixed ...$args): DepositRequest
    {
        /** @var DepositRequest $deposit */
        $deposit = $args[0];
        $adminNote = $args[1] ?? 'Deposit verified and approved.';

        return DB::transaction(function () use ($deposit, $adminNote) {
            $deposit->update([
                'status' => 'approved',
                'admin_note' => $adminNote,
            ]);

            $account = $deposit->bankAccount;
            $account->lockForUpdate()->increment('ledger_balance', $deposit->amount);
            $account->increment('available_balance', $deposit->amount);

            // 1. Create High-Level Transaction Record
            $transaction = Transaction::create([
                'user_id' => $deposit->user_id,
                'bank_account_id' => $account->id,
                'type' => 'deposit',
                'amount' => $deposit->amount,
                'reference' => $deposit->reference,
                'status' => TransactionStatus::SUCCESSFUL,
                'description' => 'Wallet Funding via Manual Deposit',
                'metadata' => [
                    'deposit_request_id' => $deposit->id,
                ],
            ]);

            // 2. Double-entry: One entry for the user account (the other is system/off-ledger)
            LedgerEntry::create([
                'transaction_id' => $transaction->id,
                'bank_account_id' => $account->id,
                'type' => 'credit',
                'amount' => $deposit->amount,
                'reference' => $deposit->reference,
                'balance_after' => $account->available_balance,
                'description' => 'Manual Deposit Credit',
            ]);

            return $deposit;
        });
    }
}
