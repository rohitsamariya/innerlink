<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups', 'id')->onDelete('cascade')->name('fk_memberships_group');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade')->name('fk_memberships_user');
            $table->foreignId('added_by')->constrained('users', 'id')->onDelete('restrict')->name('fk_memberships_added_by');
            $table->timestampTz('joined_at')->useCurrent();
            $table->timestampTz('left_at')->nullable();
        });

        // PostgreSQL Specific Constraints & Indexes
        DB::statement("ALTER TABLE group_memberships ADD CONSTRAINT chk_memberships_chronology CHECK (left_at IS NULL OR left_at >= joined_at)");
        
        // Prevent concurrent active memberships in the same group
        DB::statement("CREATE UNIQUE INDEX uq_active_group_membership ON group_memberships USING btree (group_id, user_id) WHERE left_at IS NULL");

        // Critical temporal coverage index
        DB::statement("CREATE INDEX idx_memberships_coverage ON group_memberships USING btree (group_id, user_id, joined_at, left_at)");
        DB::statement("CREATE INDEX idx_memberships_user ON group_memberships USING btree (user_id)");
        DB::statement("CREATE INDEX idx_memberships_audit ON group_memberships USING btree (added_by)");
    }

    public function down(): void
    {
        Schema::dropIfExists('group_memberships');
    }
};
