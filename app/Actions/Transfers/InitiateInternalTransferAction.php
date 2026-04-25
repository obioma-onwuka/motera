<?php

namespace App\Actions\Transfers;

use App\Actions\BaseAction;
use App\Data\Transfers\InitiateTransferData;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use App\Services\ReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Enums\TransactionStatus;

class InitiateInternalTransferAction extends BaseAction
{
    /**
     * Execute the internal transfer action with deadlock prevention.
     */
    public function execute(mixed ...$args): Transaction
    {
        /** @var InitiateTransferData $data */
        $data = $args[0];

        return DB::transaction(function () use ($data) {
            $sender = BankAccount::findOrFail($data->sender_account_id);
            $receiver = BankAccount::where('account_number', $data->receiver_account_number)->firstOrFail();

            if ($sender->id === $receiver->id) {
                throw new Exception("You cannot transfer to the same account.");
            }

            // Prevent deadlocks by sorting locks by UUID
            $firstId = $sender->id < $receiver->id ? $sender->id : $receiver->id;
            $secondId = $sender->id < $receiver->id ? $receiver->id : $sender->id;

            $locked = BankAccount::whereIn('id', [$firstId, $secondId])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $senderAccount = $locked->get($sender->id);
            $receiverAccount = $locked->get($receiver->id);

            if ($senderAccount->is_restricted) {
                throw new Exception("Account restricted. Please contact support.");
            }

            if ($senderAccount->available_balance < $data->amount) {
                throw new Exception("Insufficient available balance.");
            }

            $reference = ReferenceGenerator::generate('TRF');

            // 1. Create Transaction record
            $transaction = Transaction::create([
                'user_id' => $senderAccount->user_id,
                'bank_account_id' => $senderAccount->id,
                'reference' => $reference,
                'type' => 'transfer',
                'amount' => $data->amount,
                'status' => TransactionStatus::SUCCESSFUL,
                'description' => $data->description ?? "Transfer to {$receiverAccount->account_number}",
                'metadata' => [
                    'recipient_account_id' => $receiverAccount->id,
                    'recipient_account_number' => $receiverAccount->account_number,
                ],
            ]);

            // 2. Create Ledger Entries
            // Debit Sender
            LedgerEntry::create([
                'transaction_id' => $transaction->id,
                'bank_account_id' => $senderAccount->id,
                'type' => 'debit',
                'amount' => $data->amount,
                'reference' => $reference,
                'balance_after' => $senderAccount->available_balance - $data->amount,
                'description' => "Transfer to {$receiverAccount->account_number}",
            ]);

            // Credit Receiver
            LedgerEntry::create([
                'transaction_id' => $transaction->id,
                'bank_account_id' => $receiverAccount->id,
                'type' => 'credit',
                'amount' => $data->amount,
                'reference' => $reference,
                'balance_after' => $receiverAccount->available_balance + $data->amount,
                'description' => "Transfer from {$senderAccount->account_number}",
            ]);

            // 3. Update Balances
            $senderAccount->decrement('ledger_balance', $data->amount);
            $senderAccount->decrement('available_balance', $data->amount);

            $receiverAccount->increment('ledger_balance', $data->amount);
            $receiverAccount->increment('available_balance', $data->amount);

            return $transaction;
        });
    }
}
