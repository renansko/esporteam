<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->booted(function () {
        $whitelist = array_filter(explode(',', env('RATE_LIMIT_WHITELIST', '')));

        RateLimiter::for('api', function (Request $request) use ($whitelist) {
            if (in_array($request->ip(), $whitelist)) {
                return Limit::none();
            }

            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.jwt'      => \App\Http\Middleware\AuthenticateJwt::class,
            'service.token' => \App\Http\Middleware\ServiceTokenMiddleware::class,
            'esporteam.admin'    => \App\Http\Middleware\RequireEsporteamAdmin::class,
            'esporteam.owner'    => \App\Http\Middleware\RequireEsporteamOwner::class,
            'require.profile'     => \App\Http\Middleware\RequireProfile::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->expectsJson() || $request->is('api/*'));

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (!($request->expectsJson() || $request->is('api/*'))) {
                return null;
            }

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Not found',
                ], 404);
            }

            return null;
        });
    })->create();
