<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_messages', function (Blueprint $table) {
            $table->string('status')->default('published')->after('body');
            $table->foreignId('moderated_by_profile_id')->nullable()->after('status')
                ->constrained('sport_profiles')->nullOnDelete();
            $table->string('moderation_reason', 240)->nullable()->after('moderated_by_profile_id');
            $table->timestamp('moderated_at')->nullable()->after('moderation_reason');
            $table->index(['event_conversation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('event_messages', function (Blueprint $table) {
            $table->dropIndex(['event_conversation_id', 'status']);
            $table->dropConstrainedForeignId('moderated_by_profile_id');
            $table->dropColumn(['status', 'moderation_reason', 'moderated_at']);
        });
    }
};
