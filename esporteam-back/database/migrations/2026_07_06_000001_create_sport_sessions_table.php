<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->foreignId('sport_id')->nullable()->constrained('sports')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type');
            $table->timestamp('starts_at');
            $table->string('location_label')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->decimal('latitude_approx', 8, 5)->nullable();
            $table->decimal('longitude_approx', 8, 5)->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('visibility')->default('public');
            $table->string('status')->default('open');
            $table->timestamps();

            $table->index(['status', 'visibility', 'starts_at']);
            $table->index(['sport_id', 'type']);
            $table->index(['city', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_sessions');
    }
};
