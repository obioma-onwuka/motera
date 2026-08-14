<?php

namespace App\Actions\Deposits;

use App\Actions\BaseAction;
use App\Enums\RequestStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\RequestAlreadyProcessedException;
use App\Models\BankAccount;
use App\Models\DepositRequest;
use App\Models\Transaction;
use App\Services\LedgerService;
use App\Services\ReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApproveDepositAction extends BaseAction
{
    /**
     * Execute the deposit approval action.
     */
    public function execute(mixed ...$args): DepositRequest
    {
        Gate::authorize('approve-deposits');

        /** @var DepositRequest $deposit */
        $deposit = $args[0];
        $adminNote = $args[1] ?? 'Deposit verified and approved.';

        return DB::transaction(function () use ($deposit, $adminNote) {
            // Re-fetch and lock the request row to prevent double-processing.
            $deposit = DepositRequest::whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($deposit->status !== RequestStatus::PENDING) {
                throw new RequestAlreadyProcessedException('This deposit request has already been processed.');
            }

            $account = BankAccount::whereKey($deposit->bank_account_id)->lockForUpdate()->firstOrFail();

            $transaction = Transaction::create([
                'user_id' => $deposit->user_id,
                'bank_account_id' => $account->id,
                'reference' => ReferenceGenerator::generate('DEP', fn ($r) => Transaction::where('reference', $r)->exists()),
                'type' => 'deposit',
                'amount' => $deposit->amount,
                'status' => TransactionStatus::SUCCESSFUL,
                'description' => 'Deposit approval',
                'metadata' => [
                    'deposit_reference' => $deposit->reference,
                ],
            ]);

            $ledger = app(LedgerService::class);
            $ledger->credit($transaction, $account, (string) $deposit->amount, 'Deposit '.$deposit->reference);
            $ledger->contra($transaction, 'debit', (string) $deposit->amount, 'Deposit '.$deposit->reference.' (system contra)');

            $deposit->update([
                'status' => RequestStatus::APPROVED,
                'admin_note' => $adminNote,
            ]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($deposit)
                ->withProperties(['amount' => $deposit->amount])
                ->log('deposit.approved');

            return $deposit;
        });
    }
}
