<?php

namespace App\Services\Llm;

use App\Models\ClusteringRun;

class CostCalculator
{
    /**
     * Calcula custo em USD para uma run, usando config/llm.php do provider
     * derivado do modelo (`claude-*` → anthropic; resto → openai).
     */
    public function usd(int $tokensIn, int $tokensOut, string $model): float
    {
        $provider = str_starts_with($model, 'claude-') ? 'anthropic' : 'openai';
        $cfg = config("llm.providers.{$provider}") ?? [];

        $costIn  = (float) ($cfg['cost_per_1m_in_usd']  ?? 0);
        $costOut = (float) ($cfg['cost_per_1m_out_usd'] ?? 0);

        return round(($tokensIn * $costIn + $tokensOut * $costOut) / 1_000_000, 6);
    }

    /**
     * Soma tokens (in + out) usados em runs do workspace no mês corrente.
     */
    public function tokensUsedThisMonth(int $workspaceId): int
    {
        return (int) ClusteringRun::query()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('COALESCE(SUM(token_usage_in), 0) + COALESCE(SUM(token_usage_out), 0) AS total')
            ->value('total');
    }

    public function monthlyBudget(): int
    {
        return (int) config('llm.monthly_token_budget_per_workspace');
    }

    public function workspaceOverBudget(int $workspaceId): bool
    {
        return $this->tokensUsedThisMonth($workspaceId) >= $this->monthlyBudget();
    }
}
