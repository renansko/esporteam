<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ideas ADD COLUMN embedding vector(1536) NULL');
            DB::statement(
                'CREATE INDEX ideas_embedding_ivfflat_idx '
                .'ON ideas USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)'
            );

            // FK real ideas.roadmap_item_id -> roadmap_items.id ON DELETE SET NULL.
            DB::statement(
                'ALTER TABLE ideas ADD CONSTRAINT ideas_roadmap_item_id_fkey '
                .'FOREIGN KEY (roadmap_item_id) REFERENCES roadmap_items(id) ON DELETE SET NULL'
            );
        } else {
            // SQLite (testes): embedding fica como JSON blob simples; sem pgvector.
            Schema::table('ideas', function (Blueprint $table) {
                $table->json('embedding')->nullable();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ideas DROP CONSTRAINT IF EXISTS ideas_roadmap_item_id_fkey');
            DB::statement('DROP INDEX IF EXISTS ideas_embedding_ivfflat_idx');
            DB::statement('ALTER TABLE ideas DROP COLUMN IF EXISTS embedding');
        } else {
            Schema::table('ideas', function (Blueprint $table) {
                $table->dropColumn('embedding');
            });
        }
    }
};
