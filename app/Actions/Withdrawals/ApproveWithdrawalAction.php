<?php

namespace App\Actions\Withdrawals;

use App\Actions\BaseAction;
use App\Models\WithdrawalRequest;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use App\Enums\TransactionStatus;

class ApproveWithdrawalAction extends BaseAction
{
    /**
     * Execute the withdrawal approval action.
     */
    public function execute(mixed ...$args): WithdrawalRequest
    {
        /** @var WithdrawalRequest $withdrawal */
        $withdrawal = $args[0];
        $adminNote = $args[1] ?? 'Withdrawal processed and approved.';

        return DB::transaction(function () use ($withdrawal, $adminNote) {
            $withdrawal->update([
                'status' => 'approved',
                'admin_note' => $adminNote,
            ]);

            $account = $withdrawal->bankAccount;
            $account->lockForUpdate();
            
            // For withdrawal, available_balance might have been deducted at request. 
            // We need to deduct ledger_balance now to finalize it if it was held.
            // Assuming simplified model where deduction happens at approval for this iteration.
            $account->decrement('ledger_balance', $withdrawal->amount);
            $account->decrement('available_balance', $withdrawal->amount);

            // 1. Create Transaction
            $transaction = Transaction::create([
                'user_id' => $withdrawal->user_id,
                'bank_account_id' => $account->id,
                'type' => 'withdrawal',
                'amount' => $withdrawal->amount,
                'reference' => $withdrawal->reference,
                'status' => TransactionStatus::SUCCESSFUL,
                'description' => "Withdrawal to {$withdrawal->bank_name} ({$withdrawal->account_number})",
                'metadata' => ['withdrawal_request_id' => $withdrawal->id],
            ]);

            // 2. Ledger Entry
            LedgerEntry::create([
                'transaction_id' => $transaction->id,
                'bank_account_id' => $account->id,
                'type' => 'debit',
                'amount' => $withdrawal->amount,
                'reference' => $withdrawal->reference,
                'balance_after' => $account->available_balance,
                'description' => 'Withdrawal Settlement',
            ]);

            return $withdrawal;
        });
    }
}
