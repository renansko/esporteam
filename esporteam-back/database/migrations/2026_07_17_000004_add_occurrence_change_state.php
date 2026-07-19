<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('occurrence_key');
            $table->boolean('is_series_override')->default(false)->after('version');
            $table->string('change_notice', 32)->nullable()->after('is_series_override');
            $table->string('cancelled_reason', 500)->nullable()->after('change_notice');
        });

        Schema::table('sport_session_series', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('publication_key');
        });
    }

    public function down(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->dropColumn(['version', 'is_series_override', 'change_notice', 'cancelled_reason']);
        });
        Schema::table('sport_session_series', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
