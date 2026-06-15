<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('role', 20);
            $table->string('token', 64);
            $table->string('status', 20)->default('PENDING');
            $table->foreignId('invited_by')->constrained('users', 'id')->onDelete('restrict')->name('fk_invitations_sender');
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
            $table->timestampsTz(); // Native Eloquent lifecycle
            
            $table->unique('token', 'uq_invitations_token'); // Implicitly creates Postgres B-Tree index
        });

        DB::statement("ALTER TABLE invitations ADD CONSTRAINT chk_invitations_role CHECK (role IN ('ADMIN', 'MANAGER', 'USER'))");
        DB::statement("ALTER TABLE invitations ADD CONSTRAINT chk_invitations_status CHECK (status IN ('PENDING', 'USED', 'EXPIRED', 'CANCELLED'))");
        
        // Partial unique index ensuring only ONE pending invitation exists per email
        DB::statement("CREATE UNIQUE INDEX uq_pending_invitation_email ON invitations USING btree (email) WHERE (status = 'PENDING')");
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
