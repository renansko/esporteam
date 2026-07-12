<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_profile_id')->nullable()->constrained('sport_profiles')->nullOnDelete();
            $table->string('operation');
            $table->string('outcome');
            $table->string('idempotency_key', 191)->unique();
            $table->json('metadata');
            $table->timestamps();

            $table->index(['sport_profile_id', 'operation', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_audit_events');
    }
};
