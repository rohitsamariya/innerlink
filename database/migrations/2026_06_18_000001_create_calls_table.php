<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->string('status', 20)->default('ringing');
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestampsTz();

            $table->index('caller_id');
            $table->index('receiver_id');
            $table->index('status');
        });

        DB::statement("ALTER TABLE calls ADD CONSTRAINT chk_calls_no_self CHECK (caller_id <> receiver_id)");
        DB::statement("ALTER TABLE calls ADD CONSTRAINT chk_calls_status CHECK (status IN ('ringing', 'accepted', 'rejected', 'missed', 'ended', 'failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
