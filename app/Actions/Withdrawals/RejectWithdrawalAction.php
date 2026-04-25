<?php

namespace App\Actions\Withdrawals;

use App\Actions\BaseAction;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;

class RejectWithdrawalAction extends BaseAction
{
    /**
     * Execute the withdrawal rejection action.
     */
    public function execute(mixed ...$args): WithdrawalRequest
    {
        /** @var WithdrawalRequest $withdrawal */
        $withdrawal = $args[0];
        $reason = $args[1];

        return DB::transaction(function () use ($withdrawal, $reason) {
            $withdrawal->update([
                'status' => 'rejected',
                'admin_note' => $reason,
            ]);

            $account = $withdrawal->bankAccount;
            // Release funds back to available_balance
            $account->lockForUpdate()->increment('available_balance', $withdrawal->amount);

            return $withdrawal;
        });
    }
}
