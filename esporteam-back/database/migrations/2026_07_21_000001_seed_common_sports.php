<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Database\Seeders\CommonSportsSeeder;

return new class extends Migration
{
    public function up(): void
    {
        (new CommonSportsSeeder)->run();
    }

    public function down(): void
    {
        DB::table('sports')
            ->whereIn('slug', CommonSportsSeeder::slugs())
            ->delete();
    }
};
