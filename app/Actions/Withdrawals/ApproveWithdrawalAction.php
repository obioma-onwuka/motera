<?php

namespace App\Actions\Withdrawals;

use App\Actions\BaseAction;
use App\Enums\RequestStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\RequestAlreadyProcessedException;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\WithdrawalRequest;
use App\Services\LedgerService;
use App\Services\ReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Settle a pending withdrawal request.
 *
 * The available_balance was already held when the request was created, so
 * approval debits the ledger_balance only (settleAvailable: false — no
 * second available_balance decrement) and posts a contra credit leg for
 * the system payout so the transaction balances to zero.
 */
class ApproveWithdrawalAction extends BaseAction
{
    /**
     * Execute the withdrawal approval action.
     *
     * @param  WithdrawalRequest  $args[0]  The request to approve.
     * @param  string  $args[1]  The admin note.
     */
    public function execute(mixed ...$args): WithdrawalRequest
    {
        Gate::authorize('approve-withdrawals');

        /** @var WithdrawalRequest $withdrawal */
        $withdrawal = $args[0];
        $adminNote = $args[1] ?? 'Withdrawal processed and approved.';

        return DB::transaction(function () use ($withdrawal, $adminNote) {
            $withdrawal = WithdrawalRequest::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($withdrawal->status !== RequestStatus::PENDING) {
                throw new RequestAlreadyProcessedException('This withdrawal request has already been processed.');
            }

            $account = BankAccount::whereKey($withdrawal->bank_account_id)->lockForUpdate()->firstOrFail();

            // 1. Create Transaction
            $transaction = Transaction::create([
                'user_id' => $withdrawal->user_id,
                'bank_account_id' => $account->id,
                'reference' => ReferenceGenerator::generate('WTH', fn ($reference) => Transaction::where('reference', $reference)->exists()),
                'type' => 'withdrawal',
                'amount' => $withdrawal->amount,
                'status' => TransactionStatus::SUCCESSFUL,
                'description' => 'Withdrawal settlement',
                'metadata' => ['withdrawal_reference' => $withdrawal->reference],
            ]);

            // 2. Ledger legs: settle ledger_balance only (the available_balance
            //    hold was already taken at request time) and balance the entry
            //    with a contra credit for the system payout.
            app(LedgerService::class)->debit($transaction, $account, (string) $withdrawal->amount, 'Withdrawal Settlement', settleAvailable: false);
            app(LedgerService::class)->contra($transaction, 'credit', (string) $withdrawal->amount, 'Withdrawal Settlement (system payout)');

            // 3. Mark the request as approved.
            $withdrawal->update([
                'status' => RequestStatus::APPROVED,
                'admin_note' => $adminNote,
            ]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($withdrawal)
                ->withProperties(['amount' => $withdrawal->amount])
                ->log('withdrawal.approved');

            return $withdrawal;
        });
    }
}
