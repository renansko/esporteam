<?php

namespace App\Services\Clustering;

/**
 * Valida cada decisão emitida pelo LLM antes de persistir.
 *
 * Regras (seção 5 / 8 da issue #7):
 * - effort=0 é coerced para 1 (warning anotado no rationale).
 * - impact/reach/effort fora de [1,5] => decisão REJEITADA (não a run).
 * - idea_id deve pertencer ao set de ideias do workspace (passado em $allowedIdeaIds).
 * - action=assign exige roadmap_item_id em $allowedItemIds.
 * - action=create exige new_item com title/description e breakdown.
 *
 * @wiki app/brain/services/ClusteringDecisionValidator.md
 */
class ClusteringDecisionValidator
{
    /** @var list<string> */
    public array $warnings = [];

    /**
     * @param  array<string,mixed>  $decision
     * @param  list<int>            $allowedIdeaIds
     * @param  list<int>            $allowedItemIds
     * @return array<string,mixed>|null  null se rejeitada; senão retorna a decisão saneada.
     */
    public function validate(array $decision, array $allowedIdeaIds, array $allowedItemIds): ?array
    {
        $ideaId = (int) ($decision['idea_id'] ?? 0);
        if ($ideaId <= 0 || ! in_array($ideaId, $allowedIdeaIds, true)) {
            $this->warnings[] = "rejected: idea_id={$ideaId} not in workspace";
            return null;
        }

        $action = (string) ($decision['action'] ?? '');

        if ($action === 'assign') {
            $itemId = (int) ($decision['roadmap_item_id'] ?? 0);
            if ($itemId <= 0 || ! in_array($itemId, $allowedItemIds, true)) {
                $this->warnings[] = "rejected: roadmap_item_id={$itemId} not in workspace";
                return null;
            }
            return [
                'idea_id'         => $ideaId,
                'action'          => 'assign',
                'roadmap_item_id' => $itemId,
                'rationale'       => (string) ($decision['rationale'] ?? ''),
            ];
        }

        if ($action === 'create') {
            $item = $decision['new_item'] ?? null;
            if (! is_array($item)) {
                $this->warnings[] = 'rejected: create without new_item';
                return null;
            }
            $title = trim((string) ($item['title'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            if ($title === '' || $description === '') {
                $this->warnings[] = 'rejected: create without title/description';
                return null;
            }

            $breakdown = $this->validateBreakdown($item);
            if ($breakdown === null) {
                return null;
            }

            return [
                'idea_id'   => $ideaId,
                'action'    => 'create',
                'new_item'  => [
                    'title'           => $title,
                    'description'     => $description,
                    'score_breakdown' => $breakdown,
                ],
                'rationale' => (string) ($decision['rationale'] ?? ''),
            ];
        }

        $this->warnings[] = "rejected: unknown action='{$action}'";
        return null;
    }

    /**
     * @param  array<string,mixed>  $item
     * @return array{impact:int,reach:int,effort:int}|null
     */
    private function validateBreakdown(array $item): ?array
    {
        $impact = (int) ($item['impact'] ?? 0);
        $reach  = (int) ($item['reach']  ?? 0);
        $effort = (int) ($item['effort'] ?? 0);

        if ($effort === 0) {
            $this->warnings[] = 'coerced: effort=0 to 1';
            $effort = 1;
        }
        foreach (['impact' => $impact, 'reach' => $reach, 'effort' => $effort] as $key => $val) {
            if ($val < 1 || $val > 5) {
                $this->warnings[] = "rejected: {$key}={$val} out of [1,5]";
                return null;
            }
        }

        return ['impact' => $impact, 'reach' => $reach, 'effort' => $effort];
    }
}
