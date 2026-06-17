<?php

namespace Database\Seeders;

use App\Domains\Identity\Enums\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::table('users')->insertOrIgnore([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => Role::ADMIN->value,
            'is_enabled' => true,
            'is_muted' => false,
        ]);
    }
}
