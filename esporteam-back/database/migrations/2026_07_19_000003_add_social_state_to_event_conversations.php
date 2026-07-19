<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_messages', function (Blueprint $table) {
            $table->foreignId('reply_to_event_message_id')->nullable()->after('event_conversation_id')
                ->constrained('event_messages')->nullOnDelete();
        });

        Schema::create('event_message_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentioned_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['event_message_id', 'mentioned_profile_id']);
        });

        Schema::create('event_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_profile_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamps();
            $table->unique(['event_message_id', 'sport_profile_id', 'emoji']);
        });

        Schema::create('event_conversation_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_read_message_id')->default(0);
            $table->timestamps();
            $table->unique(['event_conversation_id', 'sport_profile_id']);
        });

        Schema::create('event_conversation_mutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['event_conversation_id', 'sport_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_conversation_mutes');
        Schema::dropIfExists('event_conversation_reads');
        Schema::dropIfExists('event_message_reactions');
        Schema::dropIfExists('event_message_mentions');
        Schema::table('event_messages', fn (Blueprint $table) => $table->dropConstrainedForeignId('reply_to_event_message_id'));
    }
};
