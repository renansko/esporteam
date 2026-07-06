<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('display_name');
            $table->text('bio')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->decimal('latitude_approx', 9, 6)->nullable();
            $table->decimal('longitude_approx', 9, 6)->nullable();
            $table->string('visibility')->default('public');
            $table->string('avatar_url')->nullable();
            $table->timestamps();

            $table->index(['visibility', 'city', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_profiles');
    }
};
