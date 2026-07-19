<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->uuid('upload_id')->unique();
            $table->string('upload_key')->unique();
            $table->string('safe_key')->nullable()->unique();
            $table->string('thumbnail_key')->nullable()->unique();
            $table->string('declared_mime', 64);
            $table->string('detected_mime', 64)->nullable();
            $table->unsignedBigInteger('byte_size')->default(0);
            $table->string('status', 24)->default('processing');
            $table->string('rejection_code', 64)->nullable();
            $table->timestamp('upload_expires_at');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['event_conversation_id', 'author_profile_id', 'status']);
        });

        Schema::create('event_message_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_media_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('position');
            $table->timestamps();
            $table->unique(['event_message_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_message_media');
        Schema::dropIfExists('conversation_media');
    }
};
