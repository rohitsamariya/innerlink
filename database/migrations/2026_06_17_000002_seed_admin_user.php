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

        $user = DB::table('users')->where('email', 'admin@gmail.com')->first();
        if ($user) {
            DB::table('user_status_periods')->updateOrInsert(
                ['user_id' => $user->id, 'status' => 'ENABLED'],
                ['start_time' => now(), 'end_time' => null]
            );
        }
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin@gmail.com')->delete();
    }
};
