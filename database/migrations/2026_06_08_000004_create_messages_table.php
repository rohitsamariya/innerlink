<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups', 'id')->onDelete('cascade')->name('fk_messages_group');
            $table->foreignId('sender_id')->constrained('users', 'id')->onDelete('restrict')->name('fk_messages_sender');
            $table->text('message_text');
            $table->timestampTz('sent_at')->useCurrent();
        });

        // Room ordering index
        DB::statement("CREATE INDEX idx_messages_room_order ON messages USING btree (group_id, sent_at DESC)");
        
        // Expression GIN Index for Full Text Search (Simple Dictionary)
        DB::statement("CREATE INDEX idx_messages_fts ON messages USING gin (to_tsvector('simple', coalesce(message_text, '')))");
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
