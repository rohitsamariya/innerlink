<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users', 'id')->onDelete('restrict')->name('fk_private_sender');
            $table->foreignId('receiver_id')->constrained('users', 'id')->onDelete('restrict')->name('fk_private_receiver');
            $table->text('message_text');
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('sent_at')->useCurrent();
        });

        // Constraint to prevent self-messaging
        DB::statement("ALTER TABLE private_messages ADD CONSTRAINT chk_private_messages_distinct CHECK (sender_id <> receiver_id)");
        
        // Expression index for rapid 1-to-1 conversation history
        DB::statement("CREATE INDEX idx_private_conversation ON private_messages USING btree (LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id), sent_at DESC)");
        
        // Partial index targeting unread messages only
        DB::statement("CREATE INDEX idx_private_unread ON private_messages USING btree (receiver_id) WHERE (read_at IS NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('private_messages');
    }
};
