<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_session_series_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_session_series_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['sport_session_series_id', 'sport_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_session_series_followers');
    }
};
