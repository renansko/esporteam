<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_offering_id')->constrained('class_offerings')->cascadeOnDelete();
            $table->foreignId('sport_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->string('status')->default('interested');
            $table->timestamps();

            $table->unique(['class_offering_id', 'sport_profile_id']);
            $table->index(['sport_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_interests');
    }
};
