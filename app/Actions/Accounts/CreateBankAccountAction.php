<?php

namespace App\Actions\Accounts;

use App\Actions\BaseAction;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Support\Str;

class CreateBankAccountAction extends BaseAction
{
    /**
     * Execute the action to create a bank account for a user.
     */
    public function execute(mixed ...$args): BankAccount
    {
        /** @var User $user */
        $user = $args[0];
        $currency = $args[1] ?? 'NGN';

        return BankAccount::create([
            'user_id' => $user->id,
            'account_number' => $this->generateAccountNumber(),
            'currency' => $currency,
            'status' => \App\Enums\AccountStatus::ACTIVE,
            'tier' => \App\Enums\KycTier::TIER_1,
        ]);
    }

    /**
     * Generate a unique 10-digit account number.
     */
    protected function generateAccountNumber(): string
    {
        do {
            $number = '00' . Str::random(8); // Simple simulation
            $number = substr(str_shuffle('0123456789'), 0, 10);
        } while (BankAccount::where('account_number', $number)->exists());

        return $number;
    }
}
