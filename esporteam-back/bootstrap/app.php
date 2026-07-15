<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.service' => \App\Http\Middleware\AuthenticateViaAuthService::class,
            'require.profile' => \App\Http\Middleware\RequireProfile::class,
            'adult.eligible' => \App\Http\Middleware\RequireAdultEligibility::class,
            'throttle.user' => \App\Http\Middleware\ThrottleAuthenticatedUser::class,
            'throttle.bio-suggestion' => \App\Http\Middleware\ThrottleBioSuggestion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (!$request->expectsJson()) {
                return null;
            }
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (!$request->expectsJson()) {
                return null;
            }
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Unauthenticated.',
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (!$request->expectsJson()) {
                return null;
            }
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'This action is unauthorized.',
                'code' => $e->getMessage() === 'adult_eligibility_required' ? 'adult_eligibility_required' : null,
            ], 403);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (!$request->expectsJson()) {
                return null;
            }
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
            ], 404);
        });
    })->create();
