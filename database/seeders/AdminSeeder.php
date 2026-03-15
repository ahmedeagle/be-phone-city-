<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'المدير',
                'password' => Hash::make('CityPhone@Admin2024!'), // Strong default password - CHANGE on first login
            ]
        );

        // Assign owner role to the first admin
        $ownerRole = Role::where('name', 'owner')->where('guard_name', 'admin')->first();
        if ($ownerRole && !$admin->hasRole('owner')) {
            $admin->assignRole($ownerRole);
        }
    }
}
