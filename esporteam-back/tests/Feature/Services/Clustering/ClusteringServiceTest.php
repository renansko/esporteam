<?php

use App\Enums\ClusteringRunStatus;
use App\Enums\RoadmapItemOrigin;
use App\Models\ClusteringRun;
use App\Models\Idea;
use App\Models\RoadmapItem;
use App\Services\Clustering\CircuitBreaker;
use App\Services\Clustering\ClusteringDecisionValidator;
use App\Services\Clustering\ClusteringPreClusterStrategy;
use App\Services\Clustering\ClusteringPromptLoader;
use App\Services\Clustering\ClusteringService;
use App\Services\Clustering\FallbackClusteringStrategy;
use App\Services\Clustering\LlmClusteringStrategy;
use App\Services\Llm\Contracts\LlmChatResponse;
use App\Services\Llm\Drivers\FakeLlmClient;
use App\Services\Llm\LlmException;
use Illuminate\Support\Facades\Cache;

function makeService(FakeLlmClient $fake): ClusteringService
{
    $llmStrategy = new LlmClusteringStrategy(
        $fake,
        new ClusteringPromptLoader(),
        new ClusteringPreClusterStrategy(),
        new ClusteringDecisionValidator(),
    );
    return new ClusteringService(
        $llmStrategy,
        new FallbackClusteringStrategy(),
        new CircuitBreaker(Cache::store('array'), threshold: 5, windowSeconds: 60),
    );
}

beforeEach(function () {
    config()->set('llm.clustering_prompt', 'clustering_v1');
    config()->set('llm.clustering_model', 'claude-haiku-4-5-20251001');
});

it('runs happy path creating new items from LLM decisions', function () {
    $ws = 99;
    $run = ClusteringRun::factory()->create(['workspace_id' => $ws]);
    $idea1 = Idea::factory()->create(['workspace_id' => $ws]);
    $idea2 = Idea::factory()->create(['workspace_id' => $ws]);

    $fake = new FakeLlmClient();
    $fake->queue(new LlmChatResponse(
        content: json_encode([
            'decisions' => [
                [
                    'idea_id'  => $idea1->id,
                    'action'   => 'create',
                    'new_item' => ['title' => 'Exportar', 'description' => 'export', 'impact' => 4, 'reach' => 3, 'effort' => 2],
                    'rationale'=> 'new theme',
                ],
                [
                    'idea_id'  => $idea2->id,
                    'action'   => 'create',
                    'new_item' => ['title' => 'Notificar', 'description' => 'notif', 'impact' => 3, 'reach' => 4, 'effort' => 1],
                    'rationale'=> 'second theme',
                ],
            ],
            'summary' => 'two new themes',
        ]),
        modelUsed: 'claude-haiku-4-5-20251001',
        tokensIn: 500,
        tokensOut: 100,
        tokensCached: 200,
        finishReason: 'end_turn',
    ));

    makeService($fake)->executeRun($run);
    $run->refresh();

    expect($run->status)->toBe(ClusteringRunStatus::Done)
        ->and($run->items_created)->toBe(2)
        ->and($run->fallback_used)->toBeFalse()
        ->and($run->token_usage_in)->toBe(500)
        ->and(RoadmapItem::where('workspace_id', $ws)->count())->toBe(2);

    $idea1->refresh();
    expect($idea1->roadmap_item_id)->not->toBeNull();
});

it('falls back when LLM throws after retries', function () {
    $ws = 11;
    $run = ClusteringRun::factory()->create(['workspace_id' => $ws]);
    Idea::factory()->create(['workspace_id' => $ws]);
    Idea::factory()->create(['workspace_id' => $ws]);

    $fake = new FakeLlmClient();
    // 3 falhas (1 inicial + 2 retries).
    foreach (range(1, 3) as $_) {
        $fake->queue(LlmException::http(500, 'boom'));
    }

    makeService($fake)->executeRun($run);
    $run->refresh();

    expect($run->status)->toBe(ClusteringRunStatus::Done)
        ->and($run->fallback_used)->toBeTrue()
        ->and($run->failure_reason)->toContain('500')
        ->and(RoadmapItem::where('workspace_id', $ws)->where('origin', RoadmapItemOrigin::Fallback->value)->count())->toBe(2);
});

it('falls back when JSON is malformed', function () {
    $ws = 12;
    $run = ClusteringRun::factory()->create(['workspace_id' => $ws]);
    Idea::factory()->create(['workspace_id' => $ws]);

    $fake = new FakeLlmClient();
    $fake->queue(new LlmChatResponse(
        content: 'not json at all',
        modelUsed: 'm', tokensIn: 1, tokensOut: 0, tokensCached: 0, finishReason: 'stop',
    ));

    makeService($fake)->executeRun($run);
    $run->refresh();

    expect($run->status)->toBe(ClusteringRunStatus::Done)
        ->and($run->fallback_used)->toBeTrue()
        ->and($run->failure_reason)->toContain('parse')
        ->and(RoadmapItem::where('workspace_id', $ws)->where('origin', RoadmapItemOrigin::Fallback->value)->count())->toBe(1);
});

it('routes hallucinated idea_id to fallback within same run', function () {
    $ws = 13;
    $run = ClusteringRun::factory()->create(['workspace_id' => $ws]);
    $real = Idea::factory()->create(['workspace_id' => $ws]);

    $fake = new FakeLlmClient();
    $fake->queue(new LlmChatResponse(
        content: json_encode([
            'decisions' => [
                ['idea_id' => 9999, 'action' => 'create', 'new_item' => ['title' => 'T', 'description' => 'D', 'impact' => 3, 'reach' => 3, 'effort' => 3]],
            ],
        ]),
        modelUsed: 'm', tokensIn: 10, tokensOut: 5, tokensCached: 0, finishReason: 'stop',
    ));

    makeService($fake)->executeRun($run);
    $run->refresh();

    // A decisão hallucinated foi rejeitada — então a Idea real cai pro fallback.
    expect(RoadmapItem::where('workspace_id', $ws)->where('origin', RoadmapItemOrigin::Fallback->value)->count())->toBe(1);
    expect($run->status)->toBe(ClusteringRunStatus::Done);
});

it('skips run with no unclustered ideas', function () {
    $ws = 14;
    $run = ClusteringRun::factory()->create(['workspace_id' => $ws]);

    $fake = new FakeLlmClient();
    makeService($fake)->executeRun($run);
    $run->refresh();

    expect($run->status)->toBe(ClusteringRunStatus::Done)
        ->and($run->items_created)->toBe(0)
        ->and($fake->calls())->toBe([]);
});

it('jumps straight to fallback when circuit breaker is open', function () {
    $ws = 15;
    $cb = new CircuitBreaker(Cache::store('array'), threshold: 1, windowSeconds: 60);
    $cb->recordFailure($ws); // já abre o circuito

    $run = ClusteringRun::factory()->create(['workspace_id' => $ws]);
    Idea::factory()->create(['workspace_id' => $ws]);

    $fake = new FakeLlmClient();
    $service = new ClusteringService(
        new LlmClusteringStrategy(
            $fake,
            new ClusteringPromptLoader(),
            new ClusteringPreClusterStrategy(),
            new ClusteringDecisionValidator(),
        ),
        new FallbackClusteringStrategy(),
        $cb,
    );

    $service->executeRun($run);
    $run->refresh();

    expect($run->fallback_used)->toBeTrue()
        ->and($fake->calls())->toBe([]); // LLM nem foi chamado
});

it('assigns idea to existing item (no new item created)', function () {
    $ws = 16;
    $existing = RoadmapItem::factory()->create(['workspace_id' => $ws]);
    $run = ClusteringRun::factory()->create(['workspace_id' => $ws]);
    $idea = Idea::factory()->create(['workspace_id' => $ws]);

    $fake = new FakeLlmClient();
    $fake->queue(new LlmChatResponse(
        content: json_encode([
            'decisions' => [
                ['idea_id' => $idea->id, 'action' => 'assign', 'roadmap_item_id' => $existing->id, 'rationale' => 'fits'],
            ],
        ]),
        modelUsed: 'm', tokensIn: 100, tokensOut: 30, tokensCached: 0, finishReason: 'stop',
    ));

    makeService($fake)->executeRun($run);
    $run->refresh();
    $idea->refresh();

    expect($run->items_created)->toBe(0)
        ->and($run->items_assigned)->toBe(1)
        ->and($idea->roadmap_item_id)->toBe($existing->id)
        ->and(RoadmapItem::where('workspace_id', $ws)->count())->toBe(1);
});
