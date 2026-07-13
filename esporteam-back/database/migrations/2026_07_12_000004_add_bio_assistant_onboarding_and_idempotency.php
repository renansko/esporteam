<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_profiles', function (Blueprint $table) {
            $table->timestamp('bio_assistant_onboarding_completed_at')->nullable()->after('bio');
        });

        DB::table('sport_profiles')
            ->whereNotNull('bio')
            ->where('bio', '<>', '')
            ->update(['bio_assistant_onboarding_completed_at' => DB::raw('updated_at')]);

        Schema::table('bio_suggestions', function (Blueprint $table) {
            $table->string('idempotency_key', 128)->nullable()->after('context_fingerprint');
            $table->unique(['sport_profile_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::table('bio_suggestions', function (Blueprint $table) {
            $table->dropUnique(['sport_profile_id', 'idempotency_key']);
            $table->dropColumn('idempotency_key');
        });

        Schema::table('sport_profiles', function (Blueprint $table) {
            $table->dropColumn('bio_assistant_onboarding_completed_at');
        });
    }
};
