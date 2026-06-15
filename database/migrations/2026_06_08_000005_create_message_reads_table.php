<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages', 'id')->onDelete('cascade')->name('fk_reads_message');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade')->name('fk_reads_user');
            $table->timestampTz('read_at')->useCurrent();

            $table->unique(['message_id', 'user_id'], 'uq_message_reads_msg_user');
        });

        DB::statement("CREATE INDEX idx_reads_user ON message_reads USING btree (user_id)");
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reads');
    }
};
