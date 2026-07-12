<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->string('status')->default('generating');
            $table->text('generated_bio')->nullable();
            $table->json('structured_output')->nullable();
            $table->string('prompt_version');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('tokens_input')->nullable();
            $table->unsignedInteger('tokens_output')->nullable();
            $table->string('failure_code')->nullable();
            $table->string('context_fingerprint', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['sport_profile_id', 'created_at']);
            $table->index(['sport_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_suggestions');
    }
};
