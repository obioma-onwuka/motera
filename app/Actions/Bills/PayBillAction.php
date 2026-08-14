<?php

namespace App\Actions\Bills;

use App\Actions\BaseAction;
use App\Enums\TransactionStatus;
use App\Exceptions\InsufficientFundsException;
use App\Models\BankAccount;
use App\Models\Biller;
use App\Models\BillPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\LedgerService;
use App\Services\ReferenceGenerator;
use App\Services\TransactionPinService;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PayBillAction extends BaseAction
{
    /**
     * Execute the bill payment action.
     *
     * @param  User  $user
     * @param  array  $data  [biller_id, amount (string), customer_identifier, pin]
     */
    public function execute(mixed ...$args): BillPayment
    {
        /** @var User $user */
        $user = $args[0];
        /** @var array $data */
        $data = $args[1];

        $result = RateLimiter::attempt(
            "bills:{$user->id}",
            5,
            fn () => $this->pay($user, $data),
            60
        );

        if ($result === false) {
            throw new ThrottleRequestsException('Too many payment attempts. Please try again shortly.');
        }

        return $result;
    }

    /**
     * Perform the payment within a transaction. Callers must not wrap
     * this in their own transaction.
     */
    protected function pay(User $user, array $data): BillPayment
    {
        return DB::transaction(function () use ($user, $data) {
            app(TransactionPinService::class)->verify($user, $data['pin'], 'bills');

            $biller = Biller::where('id', $data['biller_id'])
                ->where('is_active', true)
                ->first();

            if (! $biller) {
                throw ValidationException::withMessages([
                    'biller_id' => 'Selected biller is unavailable.',
                ]);
            }

            $account = BankAccount::where('user_id', $user->id)
                ->oldest('created_at')
                ->lockForUpdate()
                ->firstOrFail();

            $account->assertNotRestricted();

            if ($account->available_balance < $data['amount']) {
                throw new InsufficientFundsException('Insufficient available balance.');
            }

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'bank_account_id' => $account->id,
                'reference' => ReferenceGenerator::generate(
                    'BILL',
                    fn ($reference) => Transaction::where('reference', $reference)->exists()
                ),
                'type' => 'bill_payment',
                'amount' => $data['amount'],
                'status' => TransactionStatus::SUCCESSFUL,
                'description' => 'Bill payment: '.$biller->name,
                'metadata' => [
                    'biller_id' => $biller->id,
                    'customer_identifier' => $data['customer_identifier'],
                ],
            ]);

            app(LedgerService::class)->debit(
                $transaction,
                $account,
                $data['amount'],
                'Bill payment to '.$biller->name
            );

            app(LedgerService::class)->contra(
                $transaction,
                'credit',
                $data['amount'],
                'Bill payment '.$biller->name.' (system contra)'
            );

            return BillPayment::create([
                'user_id' => $user->id,
                'bank_account_id' => $account->id,
                'biller_id' => $biller->id,
                'amount' => $data['amount'],
                'reference' => $transaction->reference,
                'customer_identifier' => $data['customer_identifier'],
                'status' => 'successful',
                'metadata' => [
                    'transaction_id' => $transaction->id,
                ],
            ]);
        });
    }
}
