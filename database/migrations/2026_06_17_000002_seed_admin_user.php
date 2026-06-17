<?php

use App\Domains\Identity\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@gmail.com'],
            [
                'full_name' => 'Rohit Samariya',
                'password' => Hash::make('123456'),
                'role' => Role::ADMIN->value,
                'is_enabled' => true,
                'is_muted' => false,
            ]
        );
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin@gmail.com')->delete();
    }
};
