<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clustering_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->string('status', 16);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('ideas_processed')->nullable();
            $table->unsignedInteger('items_created')->nullable();
            $table->unsignedInteger('items_assigned')->nullable();
            $table->string('llm_model', 64)->nullable();
            $table->string('prompt_version', 64)->nullable();
            $table->unsignedInteger('token_usage_in')->nullable();
            $table->unsignedInteger('token_usage_out')->nullable();
            $table->decimal('cache_hit_rate', 5, 2)->nullable();
            $table->unsignedInteger('pre_cluster_bundles_count')->nullable();
            $table->boolean('fallback_used')->default(false);
            $table->text('failure_reason')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'started_at']);
        });

        // Apenas 1 run ativo por workspace — unique partial em pgsql.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX clustering_runs_one_active_per_workspace_idx '
                ."ON clustering_runs (workspace_id) WHERE status = 'running'"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clustering_runs');
    }
};
