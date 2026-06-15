<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 100);
            $table->string('email', 255);
            $table->string('password', 255);
            $table->string('role', 20);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_muted')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->timestampTz('two_factor_confirmed_at')->nullable();
            $table->string('current_session_id', 255)->nullable();
            $table->timestampTz('last_seen_at')->nullable();
            $table->timestampsTz(); // Uses native Eloquent lifecycle

            $table->unique('email', 'uq_users_email'); // Implicitly creates Postgres B-Tree index
        });

        // PostgreSQL Specific Constraints & Indexes
        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('ADMIN', 'MANAGER', 'USER'))");
        DB::statement("CREATE INDEX idx_users_last_seen_at ON users USING btree (last_seen_at)");
        
        // Ensure exactly one active ADMIN exists
        DB::statement("CREATE UNIQUE INDEX uq_single_admin ON users USING btree (role) WHERE role = 'ADMIN'");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
