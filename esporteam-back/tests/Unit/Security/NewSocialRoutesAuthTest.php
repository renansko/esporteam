<?php

use Illuminate\Support\Facades\Route;

it('protects every teacher group and connection route with auth service middleware', function () {
    $protectedRoutes = [
        'api/teacher-profile',
        'api/teacher-profile/students',
        'api/teacher-profile/students/{studentProfile}',
        'api/classes',
        'api/classes/{classOffering}/interest',
        'api/groups',
        'api/groups/{group}',
        'api/groups/{group}/members',
        'api/groups/{group}/members/{profile}',
        'api/connections',
        'api/connections/{connection}',
        'api/post-match-actions',
        'api/post-match-actions/session',
        'api/reports',
    ];

    foreach ($protectedRoutes as $uri) {
        $routes = collect(Route::getRoutes())
            ->filter(fn ($route) => $route->uri() === $uri);

        expect($routes)->not->toBeEmpty("Route {$uri} was not registered.");

        $routes->each(function ($route) use ($uri) {
            expect($route->gatherMiddleware(), "Route {$uri} is missing auth.service middleware.")
                ->toContain('auth.service');
        });
    }
});
