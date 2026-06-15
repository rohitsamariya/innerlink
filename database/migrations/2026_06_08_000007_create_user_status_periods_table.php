<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_status_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade')->name('fk_status_periods_user');
            $table->string('status', 10);
            $table->timestampTz('start_time')->useCurrent();
            $table->timestampTz('end_time')->nullable();
        });

        DB::statement("ALTER TABLE user_status_periods ADD CONSTRAINT chk_status_periods_chronology CHECK (end_time IS NULL OR end_time >= start_time)");
        DB::statement("ALTER TABLE user_status_periods ADD CONSTRAINT chk_status_periods_value CHECK (status IN ('ENABLED', 'DISABLED'))");
        
        DB::statement("CREATE INDEX idx_status_periods_lookup ON user_status_periods USING btree (user_id, status, start_time, end_time)");
    }

    public function down(): void
    {
        Schema::dropIfExists('user_status_periods');
    }
};
