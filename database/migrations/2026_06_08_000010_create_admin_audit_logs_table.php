<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users', 'id')->onDelete('restrict')->name('fk_audit_admin');
            $table->string('action', 50);
            $table->string('target_type', 50)->nullable();
            $table->bigInteger('target_id')->nullable();
            $table->jsonb('payload')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("CREATE INDEX idx_audit_logs_created ON admin_audit_logs USING btree (created_at DESC)");
        DB::statement("CREATE INDEX idx_audit_logs_target ON admin_audit_logs USING btree (target_type, target_id)");

        // Add strict immutability trigger
        DB::unprepared("
            CREATE OR REPLACE FUNCTION prevent_admin_audit_modification()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Modification to immutable audit table (admin_audit_logs) is strictly prohibited.';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER enforce_admin_audit_immutability
            BEFORE UPDATE OR DELETE ON admin_audit_logs
            FOR EACH ROW EXECUTE PROCEDURE prevent_admin_audit_modification();
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS enforce_admin_audit_immutability ON admin_audit_logs;");
        DB::unprepared("DROP FUNCTION IF EXISTS prevent_admin_audit_modification();");
        Schema::dropIfExists('admin_audit_logs');
    }
};
