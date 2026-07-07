<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained('sports')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents')->nullable();
            $table->timestamp('starts_at');
            $table->string('recurrence')->nullable();
            $table->string('location_label')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->decimal('latitude_approx', 8, 5)->nullable();
            $table->decimal('longitude_approx', 8, 5)->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['sport_id', 'price_cents']);
            $table->index(['city', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_offerings');
    }
};
