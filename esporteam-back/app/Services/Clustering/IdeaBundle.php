<?php

namespace App\Services\Clustering;

use App\Models\Idea;

/**
 * Bundle de Ideias semanticamente próximas. Representante leva a "voz"
 * no prompt; siblings são juntados ao mesmo destino na decisão final.
 */
final class IdeaBundle
{
    /**
     * @param  Idea         $representative
     * @param  list<Idea>   $siblings  (não inclui representative)
     */
    public function __construct(
        public readonly Idea $representative,
        public readonly array $siblings = [],
    ) {}

    /** @return list<int> */
    public function ideaIds(): array
    {
        return [$this->representative->id, ...array_map(fn (Idea $i) => $i->id, $this->siblings)];
    }

    public function size(): int
    {
        return 1 + count($this->siblings);
    }
}
