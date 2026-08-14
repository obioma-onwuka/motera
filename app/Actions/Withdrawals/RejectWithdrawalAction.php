<?php

namespace App\Actions\Withdrawals;

use App\Actions\BaseAction;
use App\Enums\RequestStatus;
use App\Exceptions\RequestAlreadyProcessedException;
use App\Models\BankAccount;
use App\Models\WithdrawalRequest;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Reject a pending withdrawal request.
 *
 * Rejection releases the hold placed on available_balance at request time
 * (the ledger_balance was never touched, so there is nothing to reverse).
 */
class RejectWithdrawalAction extends BaseAction
{
    /**
     * Execute the withdrawal rejection action.
     *
     * @param  WithdrawalRequest  $args[0]  The request to reject.
     * @param  string  $args[1]  The admin note (rejection reason).
     */
    public function execute(mixed ...$args): WithdrawalRequest
    {
        Gate::authorize('approve-withdrawals');

        /** @var WithdrawalRequest $withdrawal */
        $withdrawal = $args[0];
        $adminNote = $args[1] ?? 'Withdrawal request rejected.';

        return DB::transaction(function () use ($withdrawal, $adminNote) {
            $withdrawal = WithdrawalRequest::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($withdrawal->status !== RequestStatus::PENDING) {
                throw new RequestAlreadyProcessedException('This withdrawal request has already been processed.');
            }

            $account = BankAccount::whereKey($withdrawal->bank_account_id)->lockForUpdate()->firstOrFail();

            // Release the single hold placed at request time.
            app(LedgerService::class)->release($account, (string) $withdrawal->amount);

            $withdrawal->update([
                'status' => RequestStatus::REJECTED,
                'admin_note' => $adminNote,
            ]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($withdrawal)
                ->withProperties(['amount' => $withdrawal->amount])
                ->log('withdrawal.rejected');

            return $withdrawal;
        });
    }
}
