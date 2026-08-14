<?php

use App\Models\LedgerEntry;
use Livewire\Livewire;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Transaction history component
|--------------------------------------------------------------------------
|
| Name resolution: `transactions.transaction-history` (-prefixed name
| required — see the other test files). The app's own views reference the
| component as `<livewire:transactions.transaction-history />` (both on
| the dashboard with `:limit="5"` and on the transactions page).
*/

beforeEach(function () {
    Volt::mount([resource_path('views/components')]);
    seedRolesAndPermissions();
});

it('skips the cash-flow chart when a limit is set (dashboard embed)', function () {
    $user = createCustomer();

    Livewire::actingAs($user);

    $component = Livewire::test('transactions.transaction-history', ['limit' => 5]);

    expect($component->get('limit'))->toBe(5);

    $chartData = $component->viewData('chartData');

    expect($chartData['labels'])->toBe([]);
    expect($chartData['credits'])->toBe([]);
    expect($chartData['debits'])->toBe([]);
});

it('builds a 7-day chart from the primary account ledger entries', function () {
    $user = createCustomer();
    $account = $user->primaryAccount;

    // Two credits and one debit today...
    LedgerEntry::factory()->create([
        'bank_account_id' => $account->id,
        'type' => 'credit',
        'amount' => 150,
    ]);

    LedgerEntry::factory()->create([
        'bank_account_id' => $account->id,
        'type' => 'credit',
        'amount' => 25,
    ]);

    LedgerEntry::factory()->create([
        'bank_account_id' => $account->id,
        'type' => 'debit',
        'amount' => 40,
    ]);

    // ...plus an entry outside the 7-day window that must not count.
    $oldEntry = LedgerEntry::factory()->create([
        'bank_account_id' => $account->id,
        'type' => 'credit',
        'amount' => 999,
    ]);

    $oldEntry->created_at = now()->subDays(10);
    $oldEntry->save();

    Livewire::actingAs($user);

    $component = Livewire::test('transactions.transaction-history');

    $chartData = $component->viewData('chartData');

    expect($chartData['labels'])->toHaveCount(7);
    expect($chartData['labels'][6])->toBe(now()->format('M d'));

    expect($chartData['credits'])->toHaveCount(7);
    expect($chartData['debits'])->toHaveCount(7);

    // Today's totals (last element) reflect only today's entries.
    expect($chartData['credits'][6])->toBe(175.0);
    expect($chartData['debits'][6])->toBe(40.0);

    // Entries outside the window are excluded from the chart.
    expect(collect($chartData['credits'])->contains(999.0))->toBeFalse();
});
