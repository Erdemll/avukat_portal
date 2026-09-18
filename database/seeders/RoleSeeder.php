<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Çalışan', 'slug' => 'employee'],
            ['name' => 'Avukat', 'slug' => 'lawyer'],
            ['name' => 'Yönetici', 'slug' => 'manager'],
        ] as $role) {
            Role::query()->updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
