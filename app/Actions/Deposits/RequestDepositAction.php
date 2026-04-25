<?php

namespace App\Actions\Deposits;

use App\Actions\BaseAction;
use App\Models\DepositRequest;
use App\Models\User;
use App\Services\ReferenceGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
            $proofPath = $data['proof']->store('deposits/proofs', 'public');
        }

        return DepositRequest::create([
            'user_id' => $user->id,
            'bank_account_id' => $user->primaryAccount->id,
            'amount' => $data['amount'],
            'reference' => ReferenceGenerator::generate('DEP'),
            'status' => 'pending',
            'proof_path' => $proofPath,
        ]);
    }
}
