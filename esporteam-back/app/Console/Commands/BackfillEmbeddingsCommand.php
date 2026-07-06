<?php

namespace App\Console\Commands;

use App\Models\Idea;
use App\Services\IdeaIngestionService;
use Illuminate\Console\Command;

class BackfillEmbeddingsCommand extends Command
{
    protected $signature = 'ideas:backfill-embeddings {--batch=200} {--workspace=}';
    protected $description = 'Gera embedding para Ideas com embedding NULL em batches.';

    public function handle(IdeaIngestionService $service): int
    {
        $batchSize = (int) $this->option('batch');
        $workspace = $this->option('workspace') ? (int) $this->option('workspace') : null;

        $count = 0;
        Idea::query()
            ->whereNull('embedding')
            ->when($workspace, fn ($q) => $q->where('workspace_id', $workspace))
            ->orderBy('id')
            ->chunkById($batchSize, function ($ideas) use ($service, &$count) {
                foreach ($ideas as $idea) {
                    $service->attachEmbedding($idea);
                    $count++;
                }
                $this->getOutput()->write('.');
            });

        $this->info("\nBackfill: {$count} idea(s) processed.");
        return Command::SUCCESS;
    }
}
