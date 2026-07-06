<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_profile_id')->unique()->constrained('sport_profiles')->cascadeOnDelete();
            $table->string('headline')->nullable();
            $table->text('credentials')->nullable();
            $table->unsignedInteger('hourly_price_cents')->nullable();
            $table->unsignedSmallInteger('service_radius_km')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['teacher_profile_id', 'student_profile_id']);
            $table->index(['student_profile_id', 'status']);
        });

        Schema::create('sport_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->foreignId('sport_id')->nullable()->constrained('sports')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('visibility')->default('private');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();

            $table->index(['visibility', 'sport_id']);
        });

        Schema::create('sport_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_group_id')->constrained('sport_groups')->cascadeOnDelete();
            $table->foreignId('sport_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['sport_group_id', 'sport_profile_id']);
            $table->index(['sport_profile_id', 'status']);
        });

        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->foreignId('target_profile_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->foreignId('profile_low_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->foreignId('profile_high_id')->constrained('sport_profiles')->cascadeOnDelete();
            $table->string('type');
            $table->string('status');
            $table->timestamps();

            $table->unique(['profile_low_id', 'profile_high_id', 'type']);
            $table->index(['requester_profile_id', 'status']);
            $table->index(['target_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connections');
        Schema::dropIfExists('sport_group_members');
        Schema::dropIfExists('sport_groups');
        Schema::dropIfExists('teacher_students');
        Schema::dropIfExists('teacher_profiles');
    }
};
