<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateTransactionPinAction extends BaseAction
{
    /**
     * Execute the PIN update action.
     */
    public function execute(mixed ...$args): User
    {
        /** @var User $user */
        $user = $args[0];
        $pin = $args[1];

        $user->update([
            'transaction_pin' => Hash::make($pin),
        ]);

        return $user;
    }
}
