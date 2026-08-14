<?php

use App\Enums\KycTier;
use App\Enums\TransactionStatus;
use App\Models\BankAccount;
use App\Models\DepositRequest;
use App\Models\KycSubmission;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Livewire\Livewire;
use Livewire\Volt\Volt;

beforeEach(function () {
    Volt::mount([resource_path('views/components')]);
    seedRolesAndPermissions();
});

it('renders the dashboard for an Operations Admin', function () {
    $admin = createAdmin('Operations Admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Operations Hub')
        ->assertSeeLivewire('admin.metrics')
        ->assertSeeLivewire('admin.volume-chart')
        ->assertSeeLivewire('admin.deposit-withdrawal-chart')
        ->assertSeeLivewire('admin.tier-donut')
        ->assertSeeLivewire('admin.user-growth-chart')
        ->assertSeeLivewire('admin.recent-transactions')
        ->assertSeeLivewire('admin.admin-sidebar');
});

it('blocks customers from the dashboard page', function () {
    $customer = createCustomer();

    $this->actingAs($customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('builds a 30-day credit vs debit series with zero-fill', function () {
    LedgerEntry::factory()->create(['type' => 'credit', 'amount' => 150]);
    LedgerEntry::factory()->create(['type' => 'debit', 'amount' => 40]);

    $old = LedgerEntry::factory()->create(['type' => 'credit', 'amount' => 999]);
    $old->created_at = now()->subDays(31);
    $old->save();

    Livewire::actingAs(createAdmin('Operations Admin'));

    $component = Livewire::test('admin.volume-chart')->assertOk();

    $config = $component->viewData('chartConfig');
    expect($config['type'])->toBe('line');
    expect($config['data']['labels'])->toHaveCount(30);
    expect($config['data']['labels'][29])->toBe(now()->format('M d'));
    expect($config['data']['datasets'][0]['data'][29])->toBe(150.0);
    expect($config['data']['datasets'][1]['data'][29])->toBe(40.0);
    expect(collect($config['data']['datasets'][0]['data'])->contains(999.0))->toBeFalse();
    expect($component->viewData('table')['rows'])->toHaveCount(30);
});

it('buckets successful deposits and withdrawals into 8 ISO weeks', function () {
    Transaction::factory()->create(['type' => 'deposit', 'amount' => 200, 'status' => TransactionStatus::SUCCESSFUL]);
    Transaction::factory()->create(['type' => 'withdrawal', 'amount' => 75, 'status' => TransactionStatus::SUCCESSFUL]);
    Transaction::factory()->create(['type' => 'deposit', 'amount' => 5000, 'status' => TransactionStatus::FAILED]);

    $old = Transaction::factory()->create(['type' => 'deposit', 'amount' => 9000, 'status' => TransactionStatus::SUCCESSFUL]);
    $old->created_at = now()->subWeeks(9);
    $old->save();

    Livewire::actingAs(createAdmin('Operations Admin'));

    $component = Livewire::test('admin.deposit-withdrawal-chart')->assertOk();
    $config = $component->viewData('chartConfig');

    expect($config['type'])->toBe('bar');
    expect($config['data']['labels'])->toHaveCount(8);
    expect($config['data']['datasets'][0]['data'][7])->toBe(200.0);
    expect($config['data']['datasets'][1]['data'][7])->toBe(75.0);
    expect(collect($config['data']['datasets'][0]['data'])->contains(9000.0))->toBeFalse();
});

it('aggregates the tier distribution in tier order', function () {
    BankAccount::factory()->create(['tier' => KycTier::TIER_1]);
    BankAccount::factory()->create(['tier' => KycTier::TIER_2]);
    BankAccount::factory()->count(3)->create(['tier' => KycTier::TIER_3]);

    Livewire::actingAs(createAdmin('Operations Admin'));

    $component = Livewire::test('admin.tier-donut')->assertOk();
    $config = $component->viewData('chartConfig');

    expect($config['type'])->toBe('doughnut');
    expect($config['data']['labels'])->toBe(['Tier 1', 'Tier 2', 'Tier 3']);
    expect($config['data']['datasets'][0]['data'])->toBe([1, 1, 3]);
    expect($config['data']['datasets'][0]['backgroundColor'])->toBe(['#60A5FA', '#3B82F6', '#1D4ED8']);
    expect($component->viewData('totalAccounts'))->toBe(5);
});

it('zero-fills the 30-day user growth series', function () {
    $admin = createAdmin('Operations Admin'); // counts as a signup today

    User::factory()->count(2)->create();

    $old = User::factory()->create();
    $old->created_at = now()->subDays(40);
    $old->save();

    Livewire::actingAs($admin);

    $config = Livewire::test('admin.user-growth-chart')->assertOk()->viewData('chartConfig');

    expect($config['data']['labels'])->toHaveCount(30);
    expect($config['data']['datasets'][0]['data'][29])->toBe(3.0); // admin + 2
    expect($config['options']['plugins']['legend']['display'])->toBeFalse();
});

it('paginates recent transactions', function () {
    Transaction::factory()->count(15)->create();

    Livewire::actingAs(createAdmin('Operations Admin'));

    $component = Livewire::test('admin.recent-transactions')->assertOk();

    $transactions = $component->viewData('transactions');
    expect($transactions)->toHaveCount(10);
    expect($transactions->total())->toBe(15);
    $component->assertSee('successful');
});

it('shows pending queue counts in the sidebar nav', function () {
    KycSubmission::factory()->count(2)->create();
    DepositRequest::factory()->count(3)->create();
    WithdrawalRequest::factory()->count(1)->create();

    Livewire::actingAs(createAdmin('Operations Admin'));

    $component = Livewire::test('admin.admin-sidebar')->assertOk();

    // Counts are permission-scoped: Operations Admin approves deposits and
    // withdrawals but cannot review KYC, so the KYC badge stays hidden.
    expect($component->viewData('pendingKyc'))->toBe(0);
    expect($component->viewData('pendingDeposits'))->toBe(3);
    expect($component->viewData('pendingWithdrawals'))->toBe(1);
    $component->assertSee('KYC Reviews');
});

it('shows the KYC badge only for admins who can review KYC', function () {
    KycSubmission::factory()->count(2)->create();
    DepositRequest::factory()->count(3)->create();

    Livewire::actingAs(createAdmin('Compliance Admin'));

    $component = Livewire::test('admin.admin-sidebar')->assertOk();

    expect($component->viewData('pendingKyc'))->toBe(2);
    expect($component->viewData('pendingDeposits'))->toBe(0);
});

it('forbids customers from the sidebar nav', function () {
    Livewire::actingAs(createCustomer());

    Livewire::test('admin.admin-sidebar')->assertForbidden();
});
