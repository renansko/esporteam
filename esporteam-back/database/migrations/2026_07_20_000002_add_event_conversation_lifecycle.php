<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_conversations', function (Blueprint $table) {
            $table->foreignId('sport_session_series_id')->nullable()->after('sport_session_id')
                ->constrained('sport_session_series')->nullOnDelete();
            $table->timestamp('archived_at')->nullable()->after('status');
            $table->unique('sport_session_series_id');
        });
        Schema::table('event_messages', function (Blueprint $table) {
            $table->string('kind', 32)->default('message')->after('status');
        });
        Schema::create('event_conversation_sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('imposed_by_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('reason', 240)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['event_conversation_id', 'sport_profile_id', 'type']);
        });
        Schema::create('event_conversation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_profile_id')->nullable()->constrained('sport_profiles')->nullOnDelete();
            $table->foreignId('target_profile_id')->nullable()->constrained('sport_profiles')->nullOnDelete();
            $table->string('action', 32);
            $table->string('reason', 240)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_conversation_audits');
        Schema::dropIfExists('event_conversation_sanctions');
        Schema::table('event_messages', fn (Blueprint $table) => $table->dropColumn('kind'));
        Schema::table('event_conversations', function (Blueprint $table) {
            $table->dropUnique(['sport_session_series_id']);
            $table->dropConstrainedForeignId('sport_session_series_id');
            $table->dropColumn('archived_at');
        });
    }
};
