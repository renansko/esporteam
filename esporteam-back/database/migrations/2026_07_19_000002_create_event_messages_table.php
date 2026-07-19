<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->uuid('client_message_id');
            $table->text('body');
            $table->timestamps();

            $table->unique(['event_conversation_id', 'author_profile_id', 'client_message_id'], 'event_messages_idempotency_unique');
            $table->index(['event_conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_messages');
    }
};
