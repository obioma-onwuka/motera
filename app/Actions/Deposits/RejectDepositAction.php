<?php

namespace App\Actions\Deposits;

use App\Actions\BaseAction;
use App\Enums\RequestStatus;
use App\Exceptions\RequestAlreadyProcessedException;
use App\Models\DepositRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RejectDepositAction extends BaseAction
{
    /**
     * Execute the deposit rejection action.
     */
    public function execute(mixed ...$args): DepositRequest
    {
        Gate::authorize('approve-deposits');

        /** @var DepositRequest $deposit */
        $deposit = $args[0];
        $adminNote = $args[1] ?? '';

        return DB::transaction(function () use ($deposit, $adminNote) {
            // Re-fetch and lock the request row to prevent double-processing.
            $deposit = DepositRequest::whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($deposit->status !== RequestStatus::PENDING) {
                throw new RequestAlreadyProcessedException('This deposit request has already been processed.');
            }

            if (trim($adminNote) === '') {
                throw ValidationException::withMessages(['adminNote' => 'A note is required to reject a deposit.']);
            }

            $deposit->update([
                'status' => RequestStatus::REJECTED,
                'admin_note' => $adminNote,
            ]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($deposit)
                ->withProperties([
                    'amount' => $deposit->amount,
                    'admin_note' => $adminNote,
                ])
                ->log('deposit.rejected');

            return $deposit;
        });
    }
}
