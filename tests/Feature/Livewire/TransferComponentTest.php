<?php

use App\Models\Transaction;
use App\Models\User;
use App\Services\NameMasker;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Internal transfer component
|--------------------------------------------------------------------------
|
| Name resolution: the component file is
| resources/views/components/transfers/internal-transfer.blade.php and
| its class still extends Livewire\Volt\Component, so Volt's render()
| looks the template up through the `volt-livewire` view namespace. That
| namespace is registered by Volt::mount(...) below (the app's own
| VoltServiceProvider that used to do this was deleted from the working
| tree) and the component must be referenced by its -prefixed name so
| the template file resolves. The non- name finds the component class
| but fails to render with "View [transfers.internal-transfer] not
| found."
*/

beforeEach(function () {
    Volt::mount([resource_path('views/components')]);
    seedRolesAndPermissions();
});

/**
 * Create a customer with a PIN and the given primary-account balance.
 */
function createPinnedCustomerWithBalance(float $balance): User
{
    $user = createCustomer(['transaction_pin' => Hash::make('1234')]);

    $user->primaryAccount->update([
        'ledger_balance' => $balance,
        'available_balance' => $balance,
    ]);

    return $user;
}

it('submits a transfer only once when processTransfer is called twice', function () {
    $sender = createPinnedCustomerWithBalance(1000);
    $recipient = createCustomer();
    $recipient->primaryAccount->update([
        'ledger_balance' => 500,
        'available_balance' => 500,
    ]);

    $recipientAccountNumber = $recipient->primaryAccount->account_number;

    Livewire::actingAs($sender);

    $component = Livewire::test('transfers.internal-transfer')
        ->set('accountNumber', $recipientAccountNumber)
        ->set('amount', '100')
        ->set('pin', '1234');

    $component->call('processTransfer');

    expect(Transaction::count())->toBe(1);

    // The component catches IdempotencyViolationException, flashes an
    // info message and redirects; the second submission must not create
    // another transaction.
    $component->call('processTransfer')
        ->assertSessionHas('info')
        ->assertRedirect(route('transactions.index'));

    expect(Transaction::count())->toBe(1);
});

it('masks the recipient name after the account lookup', function () {
    $sender = createPinnedCustomerWithBalance(1000);
    $recipient = createCustomer(['name' => 'John Doe']);

    Livewire::actingAs($sender);

    $component = Livewire::test('transfers.internal-transfer')
        ->set('accountNumber', $recipient->primaryAccount->account_number);

    expect($component->get('recipientFound'))->toBeTrue();
    expect($component->get('recipientName'))->toBe(NameMasker::mask('John Doe'));
    expect($component->get('recipientName'))->not->toContain('John Doe');
});

it('rejects a wrong PIN with an error on the pin field', function () {
    $sender = createPinnedCustomerWithBalance(1000);
    $recipient = createCustomer();

    Livewire::actingAs($sender);

    Livewire::test('transfers.internal-transfer')
        ->set('accountNumber', $recipient->primaryAccount->account_number)
        ->set('amount', '100')
        ->set('pin', '9999')
        ->call('processTransfer')
        ->assertHasErrors('pin');
});

it('throttles account lookups after 10 attempts per minute', function () {
    $sender = createPinnedCustomerWithBalance(1000);
    $recipient = createCustomer();

    Livewire::actingAs($sender);

    $component = Livewire::test('transfers.internal-transfer');

    $accountNumber = $recipient->primaryAccount->account_number;

    foreach (range(1, 11) as $attempt) {
        $component->set('accountNumber', $accountNumber);
    }

    $component->assertHasErrors('accountNumber');
});

it('rejects a transfer above the available balance with an error on amount', function () {
    $sender = createPinnedCustomerWithBalance(50);
    $recipient = createCustomer();

    Livewire::actingAs($sender);

    Livewire::test('transfers.internal-transfer')
        ->set('accountNumber', $recipient->primaryAccount->account_number)
        ->set('amount', '100')
        ->set('pin', '1234')
        ->call('processTransfer')
        ->assertHasErrors('amount');
});
