<?php

namespace App\Providers;

use App\Services\WorkspaceClient;
use App\Contracts\ConversationMedia\ContentSafetyScanner;
use App\Contracts\ConversationMedia\ImageNormalizer;
use App\Contracts\ConversationMedia\MalwareScanner;
use App\Contracts\ConversationMedia\MediaStorage;
use App\Services\ConversationMedia\AwsContentSafetyScanner;
use App\Services\ConversationMedia\ClamAvMalwareScanner;
use App\Services\ConversationMedia\FilesystemMediaStorage;
use App\Services\ConversationMedia\ImageMagickNormalizer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorkspaceClient::class, function () {
            return new WorkspaceClient(rtrim((string) config('services.workspace_service.url'), '/'));
        });
        $this->app->singleton(MediaStorage::class, FilesystemMediaStorage::class);
        $this->app->singleton(MalwareScanner::class, ClamAvMalwareScanner::class);
        $this->app->singleton(ContentSafetyScanner::class, AwsContentSafetyScanner::class);
        $this->app->singleton(ImageNormalizer::class, ImageMagickNormalizer::class);
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        foreach (['created', 'updated', 'deleted', 'restored'] as $event) {
            Event::listen("eloquent.{$event}: *", fn () => Cache::increment('discovery:version'));
        }

        Request::macro('workspace_id', function (): ?int {
            $user = $this->user();

            return $user?->workspace_id ?? null;
        });

        RateLimiter::for('clustering', function (Request $request) {
            $max = (int) config('llm.rate_limit.max_attempts', 10);
            $decay = (int) config('llm.rate_limit.decay_minutes', 60);
            $key = (string) ($request->workspace_id() ?? $request->ip());

            return Limit::perMinutes($decay, $max)->by($key);
        });

    }
}
