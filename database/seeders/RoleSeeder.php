<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create owner role (protected, cannot be deleted/updated)
        $owner = Role::firstOrCreate(
            ['name' => 'owner', 'guard_name' => 'admin']
        );

        // Assign all permissions to owner role
        $allPermissions = Permission::where('guard_name', 'admin')->get();
        $owner->syncPermissions($allPermissions);

        // Create admin role with most permissions (except role management)
        $admin = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'admin']
        );
        $adminPermissions = Permission::where('guard_name', 'admin')
            ->whereNotIn('name', ['roles.show', 'roles.create', 'roles.update', 'roles.delete'])
            ->get();
        $admin->syncPermissions($adminPermissions);

        // Create manager role with view and update permissions
        $manager = Role::firstOrCreate(
            ['name' => 'manager', 'guard_name' => 'admin']
        );
        $managerPermissions = Permission::where('guard_name', 'admin')
            ->where(function ($query) {
                $query->where('name', 'like', '%.show')
                    ->orWhere('name', 'like', '%.update');
            })
            ->get();
        $manager->syncPermissions($managerPermissions);

        // Create editor role with limited permissions
        $editor = Role::firstOrCreate(
            ['name' => 'editor', 'guard_name' => 'admin']
        );
        $editorPermissions = Permission::where('guard_name', 'admin')
            ->where(function ($query) {
                $query->where('name', 'like', 'products.%')
                    ->orWhere('name', 'like', 'categories.%')
                    ->orWhere('name', 'like', 'blogs.%')
                    ->orWhere('name', 'like', 'pages.%');
            })
            ->get();
        $editor->syncPermissions($editorPermissions);
    }
}

