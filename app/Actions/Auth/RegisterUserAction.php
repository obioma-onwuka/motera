<?php

namespace App\Actions\Auth;

use App\Actions\Accounts\CreateBankAccountAction;
use App\Actions\BaseAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RegisterUserAction extends BaseAction
{
    public function __construct(
        protected CreateBankAccountAction $createBankAccountAction
    ) {}

    /**
     * Execute the registration action.
     */
    public function execute(mixed ...$args): User
    {
        $data = $args[0];

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Assign Customer role
            $user->assignRole('Customer');

            // Create primary bank account
            $this->createBankAccountAction->execute($user);

            // Send Welcome Notification
            $user->notify(new \App\Notifications\WelcomeNotification());

            return $user;
        });
    }
}
