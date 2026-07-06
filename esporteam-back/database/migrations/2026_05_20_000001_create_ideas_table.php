<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->string('source');
            $table->string('title', 255)->nullable();
            $table->text('description');
            $table->string('author_email')->nullable();
            $table->unsignedBigInteger('roadmap_item_id')->nullable();
            $table->unsignedBigInteger('source_file_id')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
        });

        // Partial index: só Postgres. SQLite (testes) ignora.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE INDEX ideas_workspace_unclustered_idx '
                .'ON ideas (workspace_id, created_at) '
                .'WHERE roadmap_item_id IS NULL'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};
