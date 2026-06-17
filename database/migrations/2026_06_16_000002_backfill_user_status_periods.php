<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            INSERT INTO user_status_periods (user_id, status, start_time)
            SELECT u.id, 'ENABLED', NOW()
            FROM users u
            WHERE NOT EXISTS (
                SELECT 1 FROM user_status_periods usp
                WHERE usp.user_id = u.id
            )
        ");
    }

    public function down(): void
    {
        DB::statement("
            DELETE FROM user_status_periods
            WHERE (user_id, start_time) IN (
                SELECT u.id, NOW()
                FROM users u
                WHERE NOT EXISTS (
                    SELECT 1 FROM user_status_periods usp
                    WHERE usp.user_id = u.id
                )
            )
        ");
    }
};
