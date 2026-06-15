<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users', 'id')->onDelete('cascade')->name('fk_exports_admin');
            $table->jsonb('filters');
            $table->string('format', 10);
            $table->string('status', 20)->default('PENDING');
            $table->string('file_path', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->timestampTz('expires_at');
            $table->timestampsTz(); // Native Eloquent lifecycle
        });

        DB::statement("ALTER TABLE export_requests ADD CONSTRAINT chk_exports_format CHECK (format IN ('CSV', 'XLSX', 'PDF'))");
        DB::statement("ALTER TABLE export_requests ADD CONSTRAINT chk_exports_status CHECK (status IN ('PENDING', 'PROCESSING', 'COMPLETED', 'FAILED'))");
        DB::statement("CREATE INDEX idx_exports_admin_history ON export_requests USING btree (admin_id, created_at DESC)");
    }

    public function down(): void
    {
        Schema::dropIfExists('export_requests');
    }
};
