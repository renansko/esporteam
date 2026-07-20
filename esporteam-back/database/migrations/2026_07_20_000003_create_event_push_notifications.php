<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('device_id', 128);
            $table->text('endpoint');
            $table->json('keys');
            $table->boolean('active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'device_id']);
            $table->unique('endpoint');
            $table->index(['user_id', 'active']);
        });

        Schema::create('push_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('push_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('push_subscription_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('recipient_profile_id');
            $table->unsignedBigInteger('event_conversation_id');
            $table->unsignedBigInteger('event_message_id')->nullable();
            $table->string('activity_type', 32);
            $table->string('idempotency_key', 191);
            $table->string('status', 24)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('failure_code')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['push_subscription_id', 'idempotency_key']);
            $table->index(['recipient_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_deliveries');
        Schema::dropIfExists('push_preferences');
        Schema::dropIfExists('push_subscriptions');
    }
};
