<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for Products
        $productPermissions = [
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'products.restore',
            'products.force-delete',
            'products.import',
            'products.export',
        ];

        // Create permissions for Categories
        $categoryPermissions = [
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
        ];

        // Create permissions for Platforms (view only - minimal platform functionality)
        $platformPermissions = [
            'platforms.view',
        ];

        // Create permissions for Stock Management
        $stockPermissions = [
            'stock.view',
            'stock.adjust',
            'stock.alerts.view',
            'stock.movements.view',
        ];

        // Create permissions for Reporting (basic reporting only)
        $reportPermissions = [
            'reports.view',
        ];

        // Create permissions for User Management
        $userPermissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.impersonate',
        ];

        // Create permissions for Role Management
        $rolePermissions = [
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
        ];

        // Combine all permissions
        $allPermissions = array_merge(
            $productPermissions,
            $categoryPermissions,
            $platformPermissions,
            $stockPermissions,
            $reportPermissions,
            $userPermissions,
            $rolePermissions
        );

        // Create all permissions
        foreach ($allPermissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Create Owner Role (replaces admin)
        $owner = Role::create([
            'name' => 'owner',
            'guard_name' => 'web',
        ]);

        // Owner gets all permissions
        $owner->givePermissionTo(Permission::all());

        // Create Staff Role
        $staff = Role::create([
            'name' => 'staff',
            'guard_name' => 'web',
        ]);

        // Staff gets limited permissions
        $staffPermissions = [
            // Product permissions (no delete)
            'products.view',
            'products.create',
            'products.edit',
            'products.import',
            'products.export',

            // Category permissions (view only)
            'categories.view',

            // Platform permissions (view only)
            'platforms.view',

            // Stock permissions (full except alerts management)
            'stock.view',
            'stock.adjust',
            'stock.alerts.view',
            'stock.movements.view',

            // Report permissions (basic reporting)
            'reports.view',
        ];

        $staff->givePermissionTo($staffPermissions);

        // Create default owner user if none exists
        if (! User::where('email', 'owner@callie.com')->exists()) {
            $ownerUser = User::create([
                'name' => 'System Owner',
                'email' => 'owner@callie.com',
                'password' => bcrypt('password'), // Change this in production
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $ownerUser->assignRole('owner');

            $this->command->info('Default owner user created: owner@callie.com / password');
        }

        // Create default staff user for testing
        if (! User::where('email', 'staff@callie.com')->exists()) {
            $staffUser = User::create([
                'name' => 'Staff Member',
                'email' => 'staff@callie.com',
                'password' => bcrypt('password'), // Change this in production
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $staffUser->assignRole('staff');

            $this->command->info('Default staff user created: staff@callie.com / password');
        }

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Owner permissions: '.$owner->permissions->count());
        $this->command->info('Staff permissions: '.$staff->permissions->count());
    }
}
