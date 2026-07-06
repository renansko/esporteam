<?php

namespace App\Services\Clustering;

use App\Models\Idea;
use Illuminate\Support\Collection;

/**
 * Agrupa Ideias por similaridade cosine antes de enviar ao LLM.
 *
 * - Em pgsql: poderia usar pgvector. Por simplicidade e portabilidade
 *   da camada de domínio, fazemos cosine em PHP — embedding já está
 *   carregado na entidade (cast simples), o custo é O(n²) sobre o
 *   conjunto ainda-não-clusterizado (mantido pequeno por design).
 * - Ideias sem embedding viram bundles de tamanho 1.
 *
 * @wiki app/brain/services/ClusteringPreClusterStrategy.md
 */
class ClusteringPreClusterStrategy
{
    public function __construct(
        private readonly float $threshold = 0.85,
        private readonly int $maxBundleSize = 10,
    ) {}

    /**
     * @param  Collection<int,Idea>  $ideas
     * @return Collection<int,IdeaBundle>
     */
    public function bundle(Collection $ideas): Collection
    {
        $remaining = $ideas->values()->all();
        $bundles = [];

        while (! empty($remaining)) {
            /** @var Idea $rep */
            $rep = array_shift($remaining);
            $repVec = $this->vector($rep);

            $siblings = [];
            $kept = [];

            foreach ($remaining as $candidate) {
                $candVec = $this->vector($candidate);
                if ($repVec === null || $candVec === null) {
                    $kept[] = $candidate;
                    continue;
                }
                $sim = $this->cosine($repVec, $candVec);
                if ($sim >= $this->threshold && count($siblings) + 1 < $this->maxBundleSize) {
                    $siblings[] = $candidate;
                } else {
                    $kept[] = $candidate;
                }
            }

            $bundles[] = new IdeaBundle($rep, $siblings);
            $remaining = $kept;
        }

        return collect($bundles);
    }

    /** @return list<float>|null */
    private function vector(Idea $idea): ?array
    {
        $raw = $idea->getAttribute('embedding');

        if ($raw === null) {
            return null;
        }
        if (is_string($raw)) {
            // pgvector vem como string "[0.1,0.2,...]" ou JSON em sqlite.
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_map('floatval', $decoded);
            }
            // pgvector format "[v1,v2,...]"
            $trimmed = trim($raw, "[] \t\n");
            if ($trimmed === '') {
                return null;
            }
            return array_map('floatval', explode(',', $trimmed));
        }
        if (is_array($raw)) {
            return array_map('floatval', $raw);
        }
        return null;
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        $n = min(count($a), count($b));
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na  += $a[$i] * $a[$i];
            $nb  += $b[$i] * $b[$i];
        }
        if ($na <= 0 || $nb <= 0) {
            return 0.0;
        }
        return $dot / (sqrt($na) * sqrt($nb));
    }
}
