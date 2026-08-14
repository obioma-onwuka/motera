<?php

namespace App\Actions\Transfers;

use App\Actions\BaseAction;
use App\Enums\TransactionStatus;
use App\Exceptions\InsufficientFundsException;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\TransferReceivedNotification;
use App\Notifications\TransferSentNotification;
use App\Services\LedgerService;
use App\Services\NameMasker;
use App\Services\ReferenceGenerator;
use App\Services\TransactionPinService;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class InitiateInternalTransferAction extends BaseAction
{
    /**
     * Execute the internal transfer action.
     *
     * @param  User  $sender
     * @param  array{recipient_account_number: string, amount: string, description: string|null, pin: string}  $data
     */
    public function execute(mixed ...$args): Transaction
    {
        /** @var User $sender */
        $sender = $args[0];
        /** @var array $data */
        $data = $args[1];

        $result = RateLimiter::attempt(
            "transfers:{$sender->id}",
            5,
            fn () => DB::transaction(function () use ($sender, $data) {
                app(TransactionPinService::class)->verify($sender, $data['pin'], 'transfer');

                // Ownership is structural: the sender's own primary account is
                // derived from the authenticated user, never from user input.
                $senderAccount = BankAccount::where('user_id', $sender->id)
                    ->oldest('created_at')
                    ->lockForUpdate()
                    ->firstOrFail();

                $senderAccount->assertNotRestricted();

                $recipientAccount = BankAccount::where('account_number', $data['recipient_account_number'])
                    ->lockForUpdate()
                    ->first();

                if (! $recipientAccount) {
                    throw ValidationException::withMessages([
                        'accountNumber' => 'Recipient account not found.',
                    ]);
                }

                if ($recipientAccount->id === $senderAccount->id) {
                    throw ValidationException::withMessages([
                        'accountNumber' => 'You cannot transfer to your own account.',
                    ]);
                }

                // Re-check the balance after acquiring the row lock.
                if ($senderAccount->available_balance < $data['amount']) {
                    throw new InsufficientFundsException('Insufficient available balance.');
                }

                $recipientUser = $recipientAccount->user;

                $tx = Transaction::create([
                    'user_id' => $sender->id,
                    'bank_account_id' => $senderAccount->id,
                    'reference' => ReferenceGenerator::generate(
                        'TRF',
                        fn ($r) => Transaction::where('reference', $r)->exists()
                    ),
                    'type' => 'transfer',
                    'amount' => $data['amount'],
                    'status' => TransactionStatus::SUCCESSFUL,
                    'description' => $data['description'] ?? null,
                    'metadata' => [
                        'recipient_name' => NameMasker::mask($recipientUser->name),
                        'recipient_account_number' => $recipientAccount->account_number,
                        'sender_name' => $sender->name,
                    ],
                ]);

                app(LedgerService::class)->debit(
                    $tx,
                    $senderAccount,
                    $data['amount'],
                    'Transfer to '.$recipientAccount->account_number
                );

                app(LedgerService::class)->credit(
                    $tx,
                    $recipientAccount,
                    $data['amount'],
                    'Transfer from '.$senderAccount->account_number
                );

                DB::afterCommit(fn () => $sender->notify(new TransferSentNotification($tx, $recipientAccount)));
                DB::afterCommit(fn () => $recipientUser->notify(new TransferReceivedNotification($tx, $senderAccount)));

                return $tx;
            }),
            60
        );

        if ($result === false) {
            throw new ThrottleRequestsException('Too many transfer attempts. Please try again shortly.');
        }

        return $result;
    }
}
