<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'panel.access',

            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',

            'products.view',
            'products.create',
            'products.update',
            'products.delete',

            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',

            'sales.view',
            'sales.create',
            'sales.cancel',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'admin' => $permissions,

            'seller' => [
                'panel.access',
                'customers.view',
                'customers.create',
                'customers.update',
                'products.view',
                'sales.view',
                'sales.create',
                'sales.cancel',
            ],

            'warehouse' => [
                'panel.access',
                'products.view',
                'products.update',
                'sales.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($rolePermissions);
        }

        // Bootstrap: primer usuario como admin
        $firstUser = User::query()->oldest('id')->first();
        if ($firstUser) {
            $firstUser->syncRoles('admin');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
