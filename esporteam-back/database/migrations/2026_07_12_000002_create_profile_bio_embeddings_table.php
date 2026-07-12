<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_bio_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_profile_id')->unique()->constrained('sport_profiles')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('model')->nullable();
            $table->string('source_hash', 64);
            $table->timestamp('embedded_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE profile_bio_embeddings ADD COLUMN embedding vector(1536) NULL');
        } else {
            Schema::table('profile_bio_embeddings', function (Blueprint $table) {
                $table->json('embedding')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_bio_embeddings');
    }
};
