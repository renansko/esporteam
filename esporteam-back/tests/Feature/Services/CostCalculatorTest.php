<?php

use App\Enums\ClusteringRunStatus;
use App\Models\ClusteringRun;
use App\Services\Llm\CostCalculator;

it('computes USD using anthropic price for claude model', function () {
    $calc = new CostCalculator;
    // 1M in * 0.25 + 1M out * 1.25 = 1.50 USD
    expect($calc->usd(1_000_000, 1_000_000, 'claude-haiku-4-5-20251001'))->toBe(1.5);
});

it('computes USD using openai price for non-claude model', function () {
    config()->set('llm.providers.openai.cost_per_1m_in_usd', 1.0);
    config()->set('llm.providers.openai.cost_per_1m_out_usd', 2.0);
    $calc = new CostCalculator;
    expect($calc->usd(500_000, 500_000, 'gpt-4o-mini'))->toBe(1.5);
});

it('sums tokens used this month for a workspace', function () {
    ClusteringRun::factory()->create(['workspace_id' => 1, 'status' => ClusteringRunStatus::Done->value, 'completed_at' => now(), 'token_usage_in' => 100, 'token_usage_out' => 200]);
    ClusteringRun::factory()->create(['workspace_id' => 1, 'status' => ClusteringRunStatus::Done->value, 'completed_at' => now(), 'token_usage_in' => 50,  'token_usage_out' => 60]);
    ClusteringRun::factory()->create(['workspace_id' => 2, 'status' => ClusteringRunStatus::Done->value, 'completed_at' => now(), 'token_usage_in' => 999, 'token_usage_out' => 999]);

    expect((new CostCalculator)->tokensUsedThisMonth(1))->toBe(410);
});

it('reports over budget when consumption >= configured budget', function () {
    config()->set('llm.monthly_token_budget_per_workspace', 500);
    ClusteringRun::factory()->create(['workspace_id' => 7, 'status' => ClusteringRunStatus::Done->value, 'completed_at' => now(), 'token_usage_in' => 300, 'token_usage_out' => 200]);
    expect((new CostCalculator)->workspaceOverBudget(7))->toBeTrue();
});
