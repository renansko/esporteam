<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->string('timezone', 64)->nullable()->after('ends_at');
            $table->string('location_label_public')->nullable()->after('location_label');
            $table->string('meeting_point_label')->nullable()->after('location_label_public');
            $table->decimal('latitude_exact', 9, 6)->nullable()->after('longitude_approx');
            $table->decimal('longitude_exact', 9, 6)->nullable()->after('latitude_exact');
            $table->string('publication_key', 128)->nullable()->after('status');

            $table->unique(['creator_profile_id', 'publication_key'], 'sport_sessions_creator_publication_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->dropUnique('sport_sessions_creator_publication_key_unique');
            $table->dropColumn([
                'ends_at',
                'timezone',
                'location_label_public',
                'meeting_point_label',
                'latitude_exact',
                'longitude_exact',
                'publication_key',
            ]);
        });
    }
};
