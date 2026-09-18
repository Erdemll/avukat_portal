<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        // Development and test accounts only; production users will be manager-created.
        foreach ([
            ['name' => 'Sistem Yöneticisi', 'email' => 'manager@example.com', 'role' => 'manager'],
            ['name' => 'Test Çalışan', 'email' => 'employee@example.com', 'role' => 'employee'],
            ['name' => 'Test Avukat', 'email' => 'lawyer@example.com', 'role' => 'lawyer'],
        ] as $user) {
            $roleId = Role::query()->where('slug', $user['role'])->value('id');

            User::query()->updateOrCreate(
                ['email' => $user['email']],
                ['name' => $user['name'], 'role_id' => $roleId, 'is_active' => true, 'password' => Hash::make('Password123!')],
            );
        }
    }
}
