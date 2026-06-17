<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_users_role");

        DB::statement("UPDATE users SET role = 'EMPLOYEE' WHERE role = 'USER'");

        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('ADMIN', 'MANAGER', 'EMPLOYEE'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_users_role");

        DB::statement("UPDATE users SET role = 'USER' WHERE role = 'EMPLOYEE'");

        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('ADMIN', 'MANAGER', 'USER'))");
    }
};
