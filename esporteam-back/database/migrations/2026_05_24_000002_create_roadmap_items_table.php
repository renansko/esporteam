<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roadmap_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->string('title', 255);
            $table->text('description');
            $table->string('status', 32)->default('em_analise');
            $table->string('visibility', 16)->default('internal');
            $table->string('origin', 32)->default('manual');
            $table->decimal('score', 8, 4)->default(0);
            $table->json('score_breakdown')->nullable();
            $table->unsignedInteger('votes_count')->default(0);
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });

        // (workspace_id, score DESC) — listing primário.
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement(
                'CREATE INDEX roadmap_items_workspace_score_idx '
                .'ON roadmap_items (workspace_id, score DESC)'
            );
            DB::statement(
                'CREATE INDEX roadmap_items_workspace_public_idx '
                .'ON roadmap_items (workspace_id, visibility) '
                ."WHERE visibility = 'public'"
            );
        } else {
            Schema::table('roadmap_items', function (Blueprint $table) {
                $table->index(['workspace_id', 'score'], 'roadmap_items_workspace_score_idx');
                $table->index(['workspace_id', 'visibility'], 'roadmap_items_workspace_public_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_items');
    }
};
