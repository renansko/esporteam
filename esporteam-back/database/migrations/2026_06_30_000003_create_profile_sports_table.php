<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_sports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->string('level');
            $table->json('goals')->nullable();
            $table->string('preferred_positions')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['sport_profile_id', 'sport_id']);
            $table->index(['sport_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_sports');
    }
};
