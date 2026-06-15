<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade')->name('fk_login_histories_user');
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->timestampTz('logged_in_at')->useCurrent();
            $table->timestampTz('logged_out_at')->nullable();
            $table->string('logout_reason', 50)->nullable();
        });

        DB::statement("ALTER TABLE login_histories ADD CONSTRAINT chk_login_histories_reason CHECK (logout_reason IN ('USER_INITIATED', 'INACTIVITY', 'FORCE_LOGOUT'))");
        DB::statement("CREATE INDEX idx_login_histories_user_session ON login_histories USING btree (user_id, logged_in_at DESC)");

        // Add strict immutability trigger
        DB::unprepared("
            CREATE OR REPLACE FUNCTION prevent_login_history_modification()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Modification to immutable audit table (login_histories) is strictly prohibited.';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER enforce_login_histories_immutability
            BEFORE DELETE ON login_histories
            FOR EACH ROW EXECUTE PROCEDURE prevent_login_history_modification();
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS enforce_login_histories_immutability ON login_histories;");
        DB::unprepared("DROP FUNCTION IF EXISTS prevent_login_history_modification();");
        Schema::dropIfExists('login_histories');
    }
};
