<?php

namespace App\Actions\Auth;

use App\Actions\BaseAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateTransactionPinAction extends BaseAction
{
    /**
     * Execute the PIN update action.
     *
     * This is the single writer of the transaction PIN column. The column
     * is intentionally NOT mass-assignable, hence forceFill().
     */
    public function execute(mixed ...$args): User
    {
        /** @var User $user */
        $user = $args[0];
        $pin = $args[1];

        $user->forceFill([
            'transaction_pin' => Hash::make($pin),
        ])->save();

        return $user;
    }
}
