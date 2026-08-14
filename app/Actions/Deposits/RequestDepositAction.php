<?php

namespace App\Actions\Deposits;

use App\Actions\BaseAction;
use App\Enums\RequestStatus;
use App\Models\DepositRequest;
use App\Models\User;
use App\Services\ReferenceGenerator;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class RequestDepositAction extends BaseAction
{
    /**
     * Execute the deposit request action.
     */
    public function execute(mixed ...$args): DepositRequest
    {
        /** @var User $user */
        $user = $args[0];
        /** @var array $data */
        $data = $args[1];

        $proofPath = null;
        if (isset($data['proof']) && $data['proof'] instanceof UploadedFile) {
            $proofPath = $data['proof']->store('deposits/proofs', 'local');
        }

        $deposit = RateLimiter::attempt(
            "deposits:{$user->id}",
            5,
            fn () => DB::transaction(function () use ($user, $data, $proofPath) {
                return DepositRequest::create([
                    'user_id' => $user->id,
                    'bank_account_id' => $user->primaryAccount->id,
                    'amount' => $data['amount'],
                    'reference' => ReferenceGenerator::generate('DEP', fn ($r) => DepositRequest::where('reference', $r)->exists()),
                    'status' => RequestStatus::PENDING,
                    'proof_path' => $proofPath,
                ]);
            }),
            60
        );

        if ($deposit === false) {
            throw new ThrottleRequestsException('Too many deposit requests. Please try again shortly.');
        }

        return $deposit;
    }
}
