<?php

use Livewire\Livewire;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Admin component authorization
|--------------------------------------------------------------------------
|
| Every admin component authorizes in `with()` (the render path), which
| fires during the initial `Livewire::test(...)` request. Livewire 4's
| RequestBroker keeps AuthorizationException in the "handled" exception
| list, so a denied `with()` surfaces as a 403 response rather than a
| thrown exception — hence `assertForbidden()` below. (A direct gate
| check `Gate::authorize(...)` does throw AuthorizationException.)
|
| Name resolution note: the components are Livewire 4 single-file
| components (⚡*.blade.php) whose classes still extend
| Livewire\Volt\Component. Volt's render() resolves the template through
| the `volt-livewire` view namespace, which the app's deleted
| VoltServiceProvider used to register via Volt::mount(...). The mount
| call below restores that registration for the test environment and the
| components must be referenced by their ⚡-prefixed names so the
| template file can be found.
*/

beforeEach(function () {
    Volt::mount([resource_path('views/components')]);
    seedRolesAndPermissions();
});

dataset('adminComponents', [
    'admin.metrics' => [
        'admin.⚡metrics', 'view-metrics', 'Operations Admin',
    ],
    'admin.audit-logs' => [
        'admin.⚡audit-logs', 'view-audit-logs', 'Compliance Admin',
    ],
    'admin.financials.deposit-review-list' => [
        'admin.financials.⚡deposit-review-list', 'approve-deposits', 'Operations Admin',
    ],
    'admin.financials.withdrawal-review-list' => [
        'admin.financials.⚡withdrawal-review-list', 'approve-withdrawals', 'Operations Admin',
    ],
    'admin.compliance.kyc-review-list' => [
        'admin.compliance.⚡kyc-review-list', 'review-kyc', 'Compliance Admin',
    ],
    'admin.biller-management' => [
        'admin.⚡biller-management', 'manage-billers', 'Operations Admin',
    ],
    'admin.customer-list' => [
        'admin.⚡customer-list', 'view-customers', 'Support Admin',
    ],
]);

it('rejects customers', function (string $component, string $permission, string $role) {
    $customer = createCustomer();

    Livewire::actingAs($customer);

    Livewire::test($component)->assertForbidden();
})->with('adminComponents');

it('rejects admin roles without the permission', function (string $component, string $permission, string $role) {
    // Support Admin only holds `view-customers`, so it is the natural
    // "admin without permission" for every component except the customer
    // list. Every seeded admin role holds `view-customers`, so for that
    // component the test uses a bespoke admin role with no permissions.
    $admin = createAdmin('Support Admin');

    if ($permission === 'view-customers') {
        Role::firstOrCreate(['name' => 'Billing Admin', 'guard_name' => 'web']);
        $admin->syncRoles(['Billing Admin']);
    }

    Livewire::actingAs($admin);

    Livewire::test($component)->assertForbidden();
})->with('adminComponents');

it('allows the correctly permissioned admin', function (string $component, string $permission, string $role) {
    $admin = createAdmin($role);

    Livewire::actingAs($admin);

    Livewire::test($component)->assertOk();
})->with('adminComponents');

it('allows the super admin through the before gate', function (string $component, string $permission, string $role) {
    $superAdmin = createAdmin('Super Admin');

    Livewire::actingAs($superAdmin);

    Livewire::test($component)->assertOk();
})->with('adminComponents');
