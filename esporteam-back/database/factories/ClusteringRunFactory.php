<?php

namespace Database\Factories;

use App\Enums\ClusteringRunStatus;
use App\Models\ClusteringRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClusteringRun>
 */
class ClusteringRunFactory extends Factory
{
    protected $model = ClusteringRun::class;

    public function definition(): array
    {
        return [
            'workspace_id'   => 1,
            'status'         => ClusteringRunStatus::Running->value,
            'started_at'     => now(),
            'llm_model'      => 'claude-haiku-4-5-20251001',
            'prompt_version' => 'clustering_v1',
            'fallback_used'  => false,
        ];
    }

    public function done(): self
    {
        return $this->state(fn () => [
            'status'         => ClusteringRunStatus::Done->value,
            'completed_at'   => now(),
            'ideas_processed' => 3,
            'items_created'  => 1,
            'items_assigned' => 2,
            'token_usage_in' => 1000,
            'token_usage_out'=> 200,
            'cache_hit_rate' => 80.5,
        ]);
    }

    public function failed(string $reason = 'test'): self
    {
        return $this->state(fn () => [
            'status'         => ClusteringRunStatus::Failed->value,
            'completed_at'   => now(),
            'failure_reason' => $reason,
        ]);
    }
}
