<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->foreignId('reported_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->string('reason');
            $table->text('details')->nullable();
            $table->string('status')->default('open');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['reported_profile_id', 'status']);
            $table->index(['reporter_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
