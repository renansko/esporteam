<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_session_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained('sports')->restrictOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->text('rules')->nullable();
            $table->text('equipment')->nullable();
            $table->string('type', 40);
            $table->date('starts_on');
            $table->time('starts_at_local');
            $table->unsignedInteger('duration_minutes');
            $table->string('timezone', 64);
            $table->unsignedInteger('interval_weeks')->default(1);
            $table->json('weekdays');
            $table->string('ends_type', 16)->default('never');
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('occurrence_count')->nullable();
            $table->string('location_label_public', 160);
            $table->string('meeting_point_label', 160);
            $table->string('city', 120);
            $table->string('region', 120);
            $table->decimal('latitude_approx', 9, 6);
            $table->decimal('longitude_approx', 9, 6);
            $table->decimal('latitude_exact', 9, 6);
            $table->decimal('longitude_exact', 9, 6);
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->string('entry_mode', 32);
            $table->string('min_level', 32)->nullable();
            $table->string('max_level', 32)->nullable();
            $table->string('visibility', 20)->default('public');
            $table->string('status', 20)->default('active');
            $table->string('publication_key', 128);
            $table->timestamps();

            $table->unique(['creator_profile_id', 'publication_key'], 'session_series_creator_publication_key_unique');
        });

        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->foreignId('sport_session_series_id')->nullable()->after('id')->constrained('sport_session_series')->nullOnDelete();
            $table->string('occurrence_key', 128)->nullable()->after('publication_key');
            $table->unique(['sport_session_series_id', 'occurrence_key'], 'sport_sessions_series_occurrence_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->dropUnique('sport_sessions_series_occurrence_key_unique');
            $table->dropConstrainedForeignId('sport_session_series_id');
            $table->dropColumn('occurrence_key');
        });

        Schema::dropIfExists('sport_session_series');
    }
};
