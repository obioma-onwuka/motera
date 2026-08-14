<?php

use App\Actions\Accounts\CreateBankAccountAction;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seed the application roles (and permissions, once defined) for tests.
 */
function seedRolesAndPermissions(): void
{
    $roles = [
        'Customer',
        'Support Admin',
        'Operations Admin',
        'Compliance Admin',
        'Super Admin',
    ];

    foreach ($roles as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $permissions = [
        'view-metrics',
        'approve-deposits',
        'approve-withdrawals',
        'review-kyc',
        'manage-billers',
        'view-customers',
        'view-audit-logs',
    ];

    foreach ($permissions as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    Role::findByName('Operations Admin')->syncPermissions([
        'view-metrics', 'approve-deposits', 'approve-withdrawals', 'manage-billers', 'view-customers',
    ]);

    Role::findByName('Compliance Admin')->syncPermissions([
        'review-kyc', 'view-audit-logs', 'view-customers',
    ]);

    Role::findByName('Support Admin')->syncPermissions([
        'view-customers',
    ]);
}

/**
 * Create a customer user with an assigned role and a bank account.
 */
function createCustomer(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole('Customer');

    app(CreateBankAccountAction::class)->execute($user);

    return $user;
}

/**
 * Create an admin user with the given staff role.
 */
function createAdmin(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}
