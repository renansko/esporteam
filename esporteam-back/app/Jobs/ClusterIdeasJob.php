<?php

namespace App\Jobs;

use App\Enums\ClusteringRunStatus;
use App\Events\ClusteringRunCompleted;
use App\Models\ClusteringRun;
use App\Services\Clustering\ClusteringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * @wiki app/brain/services/ClusteringService.md#job
 */
class ClusterIdeasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 180;

    public function __construct(public int $runId)
    {
        $this->onQueue('clustering');
    }

    public function handle(ClusteringService $service): void
    {
        $run = ClusteringRun::find($this->runId);
        if (! $run) {
            Log::channel('clustering')->error('clustering.job.missing_run', ['run_id' => $this->runId]);
            return;
        }

        try {
            $service->executeRun($run);
        } catch (\Throwable $e) {
            // Service já marca como failed em catch global; aqui só re-logamos.
            Log::channel('clustering')->error('clustering.job.threw', [
                'run_id' => $this->runId,
                'error'  => $e->getMessage(),
            ]);
        } finally {
            $run->refresh();
            event(new ClusteringRunCompleted($run));
        }
    }

    public function failed(\Throwable $e): void
    {
        $run = ClusteringRun::find($this->runId);
        if ($run && $run->status === ClusteringRunStatus::Running) {
            $run->status         = ClusteringRunStatus::Failed->value;
            $run->failure_reason = 'job failed: '.$e->getMessage();
            $run->completed_at   = now();
            $run->save();
            event(new ClusteringRunCompleted($run));
        }
    }
}
