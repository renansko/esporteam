<?php

namespace App\Console\Commands;

use App\Enums\ClusteringRunStatus;
use App\Models\ClusteringRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClusteringWatchdogCommand extends Command
{
    protected $signature   = 'clustering:watchdog';
    protected $description = 'Marca como failed runs presas em running por mais que o timeout configurado.';

    public function handle(): int
    {
        $timeout = (int) config('llm.watchdog_timeout_seconds', 600);
        $cutoff  = now()->subSeconds($timeout);

        $stuck = ClusteringRun::query()
            ->where('status', ClusteringRunStatus::Running->value)
            ->where('started_at', '<=', $cutoff)
            ->get();

        foreach ($stuck as $run) {
            $run->status         = ClusteringRunStatus::Failed->value;
            $run->failure_reason = "watchdog timeout (> {$timeout}s in running)";
            $run->completed_at   = now();
            $run->save();
            Log::channel('clustering')->warning('clustering.watchdog.killed', [
                'run_id'       => $run->id,
                'workspace_id' => $run->workspace_id,
                'started_at'   => $run->started_at?->toISOString(),
            ]);
        }

        $this->info(sprintf('Watchdog: %d run(s) killed.', $stuck->count()));
        return Command::SUCCESS;
    }
}
