<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Customer',
            'Support Admin',
            'Operations Admin',
            'Compliance Admin',
            'Super Admin',
        ];

        $roleModels = [];
        foreach ($roles as $role) {
            $roleModels[$role] = Role::firstOrCreate(['name' => $role]);
        }

        $permissionNames = [
            'view-metrics',
            'approve-deposits',
            'approve-withdrawals',
            'review-kyc',
            'manage-billers',
            'view-customers',
            'view-audit-logs',
        ];

        foreach ($permissionNames as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        $rolePermissions = [
            'Operations Admin' => [
                'view-metrics',
                'approve-deposits',
                'approve-withdrawals',
                'manage-billers',
                'view-customers',
            ],
            'Compliance Admin' => [
                'review-kyc',
                'view-audit-logs',
                'view-customers',
            ],
            'Support Admin' => [
                'view-customers',
            ],
            'Super Admin' => $permissionNames,
        ];

        foreach ($rolePermissions as $roleName => $names) {
            $roleModels[$roleName]->syncPermissions($names);
        }
    }
}
