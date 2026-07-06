<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clustering_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')
                ->constrained('clustering_runs')
                ->cascadeOnDelete();
            $table->foreignId('idea_id')
                ->constrained('ideas')
                ->cascadeOnDelete();
            $table->foreignId('roadmap_item_id')
                ->constrained('roadmap_items')
                ->cascadeOnDelete();
            $table->string('action', 16);
            $table->text('rationale');
            $table->timestamp('created_at')->nullable();

            $table->index('idea_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clustering_decisions');
    }
};
