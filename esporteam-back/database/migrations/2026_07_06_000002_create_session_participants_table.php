<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_session_id')->constrained('sport_sessions')->cascadeOnDelete();
            $table->foreignId('sport_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->string('status')->default('joined');
            $table->timestamps();

            $table->unique(['sport_session_id', 'sport_profile_id']);
            $table->index(['sport_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_participants');
    }
};
