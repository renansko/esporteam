<?php

namespace App\Providers;

use App\Services\WorkspaceClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorkspaceClient::class, function () {
            return new WorkspaceClient(rtrim((string) config('services.workspace_service.url'), '/'));
        });
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Request::macro('workspace_id', function (): ?int {
            $user = $this->user();
            return $user?->workspace_id ?? null;
        });

        RateLimiter::for('clustering', function (Request $request) {
            $max   = (int) config('llm.rate_limit.max_attempts', 10);
            $decay = (int) config('llm.rate_limit.decay_minutes', 60);
            $key   = (string) ($request->workspace_id() ?? $request->ip());
            return Limit::perMinutes($decay, $max)->by($key);
        });
    }
}
