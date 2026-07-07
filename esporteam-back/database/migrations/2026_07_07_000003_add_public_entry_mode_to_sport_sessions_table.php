<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->string('entry_mode')->default('publica_direta')->after('requires_approval');
            $table->string('min_level')->nullable()->after('entry_mode');
            $table->string('max_level')->nullable()->after('min_level');
        });

        DB::table('sport_sessions')
            ->where('requires_approval', true)
            ->update(['entry_mode' => 'publica_aprovacao']);
    }

    public function down(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table) {
            $table->dropColumn(['entry_mode', 'min_level', 'max_level']);
        });
    }
};
