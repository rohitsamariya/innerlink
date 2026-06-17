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
            WHERE start_time = (
                SELECT MAX(start_time) FROM user_status_periods usp2
                WHERE usp2.user_id = user_status_periods.user_id
            )
            AND NOT EXISTS (
                SELECT 1 FROM user_status_periods usp3
                WHERE usp3.user_id = user_status_periods.user_id
                AND usp3.start_time < user_status_periods.start_time
            )
        ");
    }
};
