<?php

use App\Enums\RequestStatus;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Withdrawal form component
|--------------------------------------------------------------------------
|
| Name resolution: same situation as the other  single-file components —
| Volt::mount(...) below restores the `volt-livewire` view namespace that
| the app's deleted VoltServiceProvider used to register, and the
| component is referenced by its -prefixed name
| (`withdrawals.withdrawal-form`). Note that the app's own view
| (user/withdrawals/create.blade.php) references
| `<livewire:withdrawals.withdrawal-form />` WITHOUT the  prefix, which
| cannot resolve the template file.
*/

beforeEach(function () {
    Volt::mount([resource_path('views/components')]);
    seedRolesAndPermissions();
});

/**
 * Create a customer with PIN 1234 and the given primary-account balance.
 */
function createWithdrawalCustomer(float $balance): User
{
    $user = createCustomer(['transaction_pin' => Hash::make('1234')]);

    $user->primaryAccount->update([
        'ledger_balance' => $balance,
        'available_balance' => $balance,
    ]);

    return $user;
}

/**
 * The valid bank destination payload shared by the tests.
 */
function validWithdrawalFields(): array
{
    return [
        'amount' => '500',
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
        'account_name' => 'John Doe',
    ];
}

it('rejects a submission without a PIN', function () {
    $user = createWithdrawalCustomer(1000);

    Livewire::actingAs($user);

    Livewire::test('withdrawals.withdrawal-form')
        ->set(validWithdrawalFields())
        ->call('submit')
        ->assertHasErrors('pin');

    expect(WithdrawalRequest::count())->toBe(0);
});

it('creates a pending withdrawal request and holds the funds', function () {
    $user = createWithdrawalCustomer(1000);

    Livewire::actingAs($user);

    Livewire::test('withdrawals.withdrawal-form')
        ->set(validWithdrawalFields())
        ->set('pin', '1234')
        ->call('submit')
        ->assertHasNoErrors();

    $request = WithdrawalRequest::sole();

    expect($request->status)->toBe(RequestStatus::PENDING);
    expect($request->amount)->toBe('500.00');
    expect($request->bank_name)->toBe('GTBank');
    expect($request->account_number)->toBe('0123456789');

    $account = $user->primaryAccount->fresh();

    expect((float) $account->available_balance)->toBe(500.0);
    expect((float) $account->ledger_balance)->toBe(1000.0);
});

it('rejects a wrong PIN with an error on the pin field', function () {
    $user = createWithdrawalCustomer(1000);

    Livewire::actingAs($user);

    Livewire::test('withdrawals.withdrawal-form')
        ->set(validWithdrawalFields())
        ->set('pin', '9999')
        ->call('submit')
        ->assertHasErrors('pin');

    expect(WithdrawalRequest::count())->toBe(0);
});
