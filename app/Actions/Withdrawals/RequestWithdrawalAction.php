<?php

namespace App\Actions\Withdrawals;

use App\Actions\BaseAction;
use App\Enums\RequestStatus;
use App\Exceptions\InsufficientFundsException;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\LedgerService;
use App\Services\ReferenceGenerator;
use App\Services\TransactionPinService;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Lifecycle model:
 *
 *   request = hold available_balance only;
 *   approve = settle ledger_balance (+contra);
 *   reject  = release hold.
 *
 * A withdrawal request places a hold on the customer's available_balance
 * inside a locked transaction so the funds cannot be spent twice. The
 * ledger_balance is only debited when an admin approves the request, and
 * rejecting the request releases the hold.
 */
class RequestWithdrawalAction extends BaseAction
{
    /**
     * Execute the withdrawal request action.
     *
     * @param  User  $args[0]  The user requesting the withdrawal.
     * @param  array  $args[1]  Request data (amount, pin, bank_name, account_number, account_name).
     */
    public function execute(mixed ...$args): WithdrawalRequest
    {
        /** @var User $user */
        $user = $args[0];
        /** @var array $data */
        $data = $args[1];

        $result = RateLimiter::attempt(
            "withdrawals:{$user->id}",
            3,
            fn () => $this->createRequest($user, $data),
            60
        );

        if ($result === false) {
            throw new ThrottleRequestsException('Too many withdrawal attempts. Please try again shortly.');
        }

        return $result;
    }

    /**
     * Verify the PIN, lock the account, check the balance, hold the funds
     * and create the pending withdrawal request.
     */
    protected function createRequest(User $user, array $data): WithdrawalRequest
    {
        return DB::transaction(function () use ($user, $data) {
            app(TransactionPinService::class)->verify($user, $data['pin'], 'withdrawal');

            $account = BankAccount::whereKey($user->primaryAccount->id)->lockForUpdate()->firstOrFail();

            $account->assertNotRestricted();

            if ($account->available_balance < $data['amount']) {
                throw new InsufficientFundsException('Insufficient available balance.');
            }

            app(LedgerService::class)->hold($account, (string) $data['amount']);

            return WithdrawalRequest::create([
                'user_id' => $user->id,
                'bank_account_id' => $account->id,
                'amount' => $data['amount'],
                'reference' => ReferenceGenerator::generate('WTH', fn ($reference) => WithdrawalRequest::where('reference', $reference)->exists()),
                'status' => RequestStatus::PENDING,
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
            ]);
        });
    }
}
