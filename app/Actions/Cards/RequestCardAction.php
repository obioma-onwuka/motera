<?php

namespace App\Actions\Cards;

use App\Actions\BaseAction;
use App\Enums\TransactionStatus;
use App\Exceptions\InsufficientFundsException;
use App\Models\BankAccount;
use App\Models\CardRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\LedgerService;
use App\Services\ReferenceGenerator;
use App\Services\TransactionPinService;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class RequestCardAction extends BaseAction
{
    /**
     * Execute the card request action.
     *
     * @param  User  $user
     * @param  array  $data  [type ('physical'|'virtual'), card_name, delivery_address, pin]
     */
    public function execute(mixed ...$args): CardRequest
    {
        /** @var User $user */
        $user = $args[0];
        /** @var array $data */
        $data = $args[1];

        $result = RateLimiter::attempt(
            "cards:{$user->id}",
            5,
            fn () => $this->request($user, $data),
            60
        );

        if ($result === false) {
            throw new ThrottleRequestsException('Too many card requests. Please try again shortly.');
        }

        return $result;
    }

    /**
     * Perform the request within a transaction. Callers must not wrap
     * this in their own transaction.
     */
    protected function request(User $user, array $data): CardRequest
    {
        $fee = $data['type'] === 'physical'
            ? (string) config('motera.limits.card_physical_fee')
            : '0';

        return DB::transaction(function () use ($user, $data, $fee) {
            $account = BankAccount::where('user_id', $user->id)
                ->oldest('created_at')
                ->lockForUpdate()
                ->firstOrFail();

            $account->assertNotRestricted();

            $transaction = null;

            if ((float) $fee > 0) {
                app(TransactionPinService::class)->verify($user, $data['pin'] ?? '', 'cards');

                if ($account->available_balance < $fee) {
                    throw new InsufficientFundsException('Insufficient available balance to cover the card fee.');
                }

                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'bank_account_id' => $account->id,
                    'reference' => ReferenceGenerator::generate(
                        'CARD',
                        fn ($reference) => Transaction::where('reference', $reference)->exists()
                    ),
                    'type' => 'card_fee',
                    'amount' => $fee,
                    'status' => TransactionStatus::SUCCESSFUL,
                    'description' => 'Card request fee',
                    'metadata' => [
                        'card_type' => $data['type'],
                    ],
                ]);

                app(LedgerService::class)->debit(
                    $transaction,
                    $account,
                    $fee,
                    'Card request fee ('.$data['type'].')'
                );

                app(LedgerService::class)->contra(
                    $transaction,
                    'credit',
                    $fee,
                    'Card request fee '.$data['type'].' (system contra)'
                );
            }

            return CardRequest::create([
                'user_id' => $user->id,
                'bank_account_id' => $account->id,
                'type' => $data['type'],
                'card_name' => $data['card_name'],
                'delivery_address' => $data['delivery_address'] ?? null,
                'fee' => $fee,
                'status' => 'pending',
                'metadata' => [
                    'transaction_reference' => $transaction?->reference,
                ],
            ]);
        });
    }
}
