<?php

use App\Enums\ClusteringRunStatus;
use App\Events\ClusteringRunCompleted;
use App\Jobs\ClusterIdeasJob;
use App\Models\ClusteringRun;
use App\Models\Idea;
use App\Services\Clustering\CircuitBreaker;
use App\Services\Clustering\ClusteringDecisionValidator;
use App\Services\Clustering\ClusteringPreClusterStrategy;
use App\Services\Clustering\ClusteringPromptLoader;
use App\Services\Clustering\ClusteringService;
use App\Services\Clustering\FallbackClusteringStrategy;
use App\Services\Clustering\LlmClusteringStrategy;
use App\Services\Llm\Contracts\LlmChatResponse;
use App\Services\Llm\Contracts\LlmClient;
use App\Services\Llm\Drivers\FakeLlmClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

it('targets the clustering queue', function () {
    expect((new ClusterIdeasJob(1))->queue)->toBe('clustering');
});

it('runs the service and dispatches ClusteringRunCompleted event', function () {
    Event::fake([ClusteringRunCompleted::class]);

    $ws = 21;
    $run = ClusteringRun::factory()->create(['workspace_id' => $ws]);
    $idea = Idea::factory()->create(['workspace_id' => $ws]);

    $fake = new FakeLlmClient();
    $fake->queue(new LlmChatResponse(
        content: json_encode([
            'decisions' => [
                [
                    'idea_id'  => $idea->id,
                    'action'   => 'create',
                    'new_item' => ['title' => 'X', 'description' => 'd', 'impact' => 3, 'reach' => 3, 'effort' => 3],
                ],
            ],
        ]),
        modelUsed: 'm', tokensIn: 10, tokensOut: 5, tokensCached: 0, finishReason: 'stop',
    ));
    app()->instance(LlmClient::class, $fake);

    // Reconstrói o ClusteringService usando o LlmClient mocked.
    app()->bind(ClusteringService::class, function () use ($fake) {
        return new ClusteringService(
            new LlmClusteringStrategy(
                $fake,
                new ClusteringPromptLoader(),
                new ClusteringPreClusterStrategy(),
                new ClusteringDecisionValidator(),
            ),
            new FallbackClusteringStrategy(),
            new CircuitBreaker(Cache::store('array'), 5, 60),
        );
    });

    (new ClusterIdeasJob($run->id))->handle(app(ClusteringService::class));
    $run->refresh();

    expect($run->status)->toBe(ClusteringRunStatus::Done);
    Event::assertDispatched(ClusteringRunCompleted::class, fn ($e) => $e->run->id === $run->id);
});

it('logs and emits event when run id is missing', function () {
    Event::fake([ClusteringRunCompleted::class]);
    (new ClusterIdeasJob(99999))->handle(app(ClusteringService::class));
    Event::assertNotDispatched(ClusteringRunCompleted::class);
});
