<?php

namespace App\Services\Clustering;

use App\Models\Idea;
use App\Models\RoadmapItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Lê o template markdown do prompt e injeta vars de runtime.
 *
 * @wiki app/brain/services/ClusteringPromptLoader.md
 */
class ClusteringPromptLoader
{
    public function path(string $version): string
    {
        return resource_path("prompts/{$version}.md");
    }

    public function load(string $version): string
    {
        $path = $this->path($version);
        if (! is_readable($path)) {
            throw new RuntimeException("Clustering prompt not found: {$path}");
        }
        return (string) file_get_contents($path);
    }

    /**
     * @param  Collection<int,RoadmapItem>  $existingItems
     * @param  Collection<int,Idea>         $ideas
     */
    public function render(string $version, Collection $existingItems, Collection $ideas): string
    {
        $template = $this->load($version);

        $itemsBlock = $existingItems->isEmpty()
            ? '(nenhum item existente)'
            : $existingItems->map(
                fn (RoadmapItem $it) => "#{$it->id} | {$it->title} | ".Str::limit((string) $it->description, 120)
            )->implode("\n");

        $ideasBlock = "id|title|description\n".$ideas->map(
            fn (Idea $i) => $i->id
                .'|'.($i->title ?? '')
                .'|'.Str::limit((string) $i->description, 200, '')
        )->implode("\n");

        return strtr($template, [
            '{{EXISTING_ITEMS}}' => $itemsBlock,
            '{{IDEAS_CSV}}'      => $ideasBlock,
        ]);
    }
}
