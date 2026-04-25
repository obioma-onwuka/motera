<?php

namespace App\Actions\Withdrawals;

use App\Actions\BaseAction;
use App\Models\WithdrawalRequest;
use App\Models\User;
use App\Services\ReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Exception;

class RequestWithdrawalAction extends BaseAction
{
    /**
     * Execute the withdrawal request action.
     */
    public function execute(mixed ...$args): WithdrawalRequest
    {
        /** @var User $user */
        $user = $args[0];
        /** @var array $data */
        $data = $args[1];

        $account = $user->primaryAccount;

        if ($account->is_restricted) {
            throw new Exception("Account restricted. Please contact support.");
        }

        if ($account->available_balance < $data['amount']) {
            throw new Exception("Insufficient balance for withdrawal.");
        }

        return DB::transaction(function () use ($user, $account, $data) {
            // Hold funds
            $account->decrement('available_balance', $data['amount']);

            return WithdrawalRequest::create([
                'user_id' => $user->id,
                'bank_account_id' => $account->id,
                'amount' => $data['amount'],
                'reference' => ReferenceGenerator::generate('WTH'),
                'status' => 'pending',
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
            ]);
        });
    }
}
