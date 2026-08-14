<?php

use App\Enums\KycStatus;
use App\Enums\KycTier;
use App\Enums\RequestStatus;
use App\Models\CardRequest;
use App\Models\DepositRequest;
use App\Models\KycSubmission;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Livewire\Livewire;
use Livewire\Volt\Volt;

beforeEach(function () {
    Volt::mount([resource_path('views/components')]);
    seedRolesAndPermissions();
});

it('renders the user dashboard for a customer', function () {
    $customer = createCustomer();

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Good day')
        ->assertSeeLivewire('dashboard.stats-row')
        ->assertSeeLivewire('dashboard.cashflow-chart')
        ->assertSeeLivewire('dashboard.tier-progress')
        ->assertSeeLivewire('transactions.transaction-history')
        ->assertSeeLivewire('nav.customer-nav');
});

it('redirects guests away from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('computes frozen funds, month totals, and pending requests from the primary account', function () {
    $customer = createCustomer();
    $account = $customer->primaryAccount;

    $account->update(['ledger_balance' => 1000, 'available_balance' => 800]);

    LedgerEntry::factory()->create(['bank_account_id' => $account->id, 'type' => 'credit', 'amount' => 150]);
    LedgerEntry::factory()->create(['bank_account_id' => $account->id, 'type' => 'debit', 'amount' => 40]);

    $old = LedgerEntry::factory()->create(['bank_account_id' => $account->id, 'type' => 'credit', 'amount' => 999]);
    $old->created_at = now()->subMonths(2);
    $old->save();

    DepositRequest::factory()->count(2)->create(['user_id' => $customer->id, 'bank_account_id' => $account->id]);
    DepositRequest::factory()->create(['user_id' => $customer->id, 'bank_account_id' => $account->id, 'status' => RequestStatus::APPROVED]);
    WithdrawalRequest::factory()->create(['user_id' => $customer->id, 'bank_account_id' => $account->id]);
    CardRequest::factory()->create(['user_id' => $customer->id, 'bank_account_id' => $account->id]);

    Livewire::actingAs($customer);

    $component = Livewire::test('dashboard.stats-row')->assertOk();

    expect($component->viewData('frozenFunds'))->toBe(200.0);
    expect($component->viewData('monthIn'))->toBe(150.0);
    expect($component->viewData('monthOut'))->toBe(40.0);
    expect($component->viewData('pendingRequests'))->toBe(4);
});

it('builds a zero-filled 30-day cashflow series for the primary account', function () {
    $customer = createCustomer();
    $account = $customer->primaryAccount;

    LedgerEntry::factory()->create(['bank_account_id' => $account->id, 'type' => 'credit', 'amount' => 150]);
    LedgerEntry::factory()->create(['bank_account_id' => $account->id, 'type' => 'debit', 'amount' => 40]);

    $old = LedgerEntry::factory()->create(['bank_account_id' => $account->id, 'type' => 'credit', 'amount' => 999]);
    $old->created_at = now()->subDays(31);
    $old->save();

    Livewire::actingAs($customer);

    $component = Livewire::test('dashboard.cashflow-chart')->assertOk();
    $config = $component->viewData('chartConfig');

    expect($component->viewData('hasAccount'))->toBeTrue();
    expect($config['type'])->toBe('line');
    expect($config['data']['labels'])->toHaveCount(30);
    expect($config['data']['labels'][29])->toBe(now()->format('M d'));
    expect($config['data']['datasets'][0]['data'][29])->toBe(150.0);
    expect($config['data']['datasets'][1]['data'][29])->toBe(40.0);
    expect(collect($config['data']['datasets'][0]['data'])->contains(999.0))->toBeFalse();
    expect($component->viewData('table')['rows'])->toHaveCount(30);
});

it('zero-fills the cashflow chart with no account', function () {
    $customer = User::factory()->create();
    $customer->assignRole('Customer');

    Livewire::actingAs($customer);

    $component = Livewire::test('dashboard.cashflow-chart')->assertOk();
    $config = $component->viewData('chartConfig');

    expect($component->viewData('hasAccount'))->toBeFalse();
    expect($config['data']['labels'])->toHaveCount(30);
    expect(array_sum($config['data']['datasets'][0]['data']))->toBe(0.0);
});

it('shows tier progress with an upgrade CTA on tier_1', function () {
    $customer = createCustomer();

    Livewire::actingAs($customer);

    $component = Livewire::test('dashboard.tier-progress')->assertOk();

    expect($component->viewData('tier'))->toBe('tier_1');
    expect($component->viewData('currentIndex'))->toBe(1);
    expect($component->viewData('nextTier'))->toBe('tier_2');
    expect($component->viewData('kycPending'))->toBeFalse();
    $component->assertSee('Upgrade to Tier 2');
    $component->assertSeeHtml('href="'.route('compliance.kyc').'"');
});

it('hides the upgrade CTA at tier_3', function () {
    $customer = createCustomer();
    $customer->primaryAccount->update(['tier' => KycTier::TIER_3]);

    Livewire::actingAs($customer);

    $component = Livewire::test('dashboard.tier-progress')->assertOk();

    expect($component->viewData('nextTier'))->toBeNull();
    $component->assertSee("You're at the highest tier.", false);
    $component->assertDontSee('Upgrade to');
});

it('shows under review instead of a CTA when KYC is pending', function () {
    $customer = createCustomer();

    KycSubmission::factory()->create([
        'user_id' => $customer->id,
        'status' => KycStatus::PENDING,
    ]);

    Livewire::actingAs($customer);

    $component = Livewire::test('dashboard.tier-progress')->assertOk();

    expect($component->viewData('kycPending'))->toBeTrue();
    $component->assertSee('under review');
    $component->assertDontSee('Upgrade to');
});

it('defaults to tier_1 with no account', function () {
    $customer = User::factory()->create();
    $customer->assignRole('Customer');

    Livewire::actingAs($customer);

    $component = Livewire::test('dashboard.tier-progress')->assertOk();

    expect($component->viewData('tier'))->toBe('tier_1');
    expect($component->viewData('currentIndex'))->toBe(1);
});

it('shows pending request badges in the customer nav', function () {
    $customer = createCustomer();
    $account = $customer->primaryAccount;

    DepositRequest::factory()->count(3)->create(['user_id' => $customer->id, 'bank_account_id' => $account->id]);
    WithdrawalRequest::factory()->create(['user_id' => $customer->id, 'bank_account_id' => $account->id]);
    CardRequest::factory()->count(2)->create(['user_id' => $customer->id, 'bank_account_id' => $account->id]);

    Livewire::actingAs($customer);

    $component = Livewire::test('nav.customer-nav')->assertOk();

    expect($component->viewData('pendingDeposits'))->toBe(3);
    expect($component->viewData('pendingWithdrawals'))->toBe(1);
    expect($component->viewData('pendingCards'))->toBe(2);
    expect($component->viewData('kycPending'))->toBeFalse();
    $component->assertSeeHtml('bg-teal-600');
    $component->assertSeeHtml('bg-red-600');
    $component->assertSeeHtml('bg-purple-600');
});

it('hides badges when there are no pending requests', function () {
    $customer = createCustomer();

    Livewire::actingAs($customer);

    $component = Livewire::test('nav.customer-nav')->assertOk();

    expect($component->viewData('pendingDeposits'))->toBe(0);
    $component->assertDontSeeHtml('bg-teal-600');
    $component->assertDontSeeHtml('bg-red-600');
    $component->assertDontSeeHtml('bg-purple-600');
});

it('scopes nav badges to the signed-in user', function () {
    $customer = createCustomer();
    $other = createCustomer();

    DepositRequest::factory()->count(5)->create([
        'user_id' => $other->id,
        'bank_account_id' => $other->primaryAccount->id,
    ]);

    Livewire::actingAs($customer);

    $component = Livewire::test('nav.customer-nav')->assertOk();

    expect($component->viewData('pendingDeposits'))->toBe(0);
});

it('shows dynamic page titles in the customer mobile header', function () {
    $customer = createCustomer();

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeHtml('<h2 class="text-lg font-bold">Dashboard</h2>');

    $this->actingAs($customer)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSeeHtml('<h2 class="text-lg font-bold">Profile</h2>');
});
