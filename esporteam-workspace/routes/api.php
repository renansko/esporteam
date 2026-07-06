<?php

use App\Http\Controllers\Admin\AdminWorkspaceController;
use App\Http\Controllers\Admin\AdminWorkspaceMemberController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'sha'    => config('app.git_sha'),
]));
Route::get('/workspaces/{workspace}/public', [WorkspaceController::class, 'publicInfo']);

Route::middleware(['auth.service', 'esporteam.admin'])->prefix('admin')->group(function () {
    Route::get('/workspaces', [AdminWorkspaceController::class, 'index']);
    Route::get('/workspaces/{workspace}', [AdminWorkspaceController::class, 'show']);
    Route::get('/workspaces/{workspace}/members', [AdminWorkspaceMemberController::class, 'index']);
    Route::patch('/workspaces/{workspace}/status', [AdminWorkspaceController::class, 'setStatus']);
});

Route::middleware('service.token')->prefix('service')->group(function () {
    Route::post('/workspaces/{workspace}/invites', [InviteController::class, 'storeService']);
    Route::post('/invites/{token}/accept', [InviteController::class, 'acceptService']);
});

Route::middleware('auth.service')->group(function () {

    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);

    Route::middleware('workspace.active')->group(function () {
        Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show']);
        Route::put('/workspaces/{workspace}', [WorkspaceController::class, 'update']);
        Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy']);

        Route::get('/workspaces/{workspace}/members', [MemberController::class, 'index']);
        Route::post('/workspaces/{workspace}/members', [MemberController::class, 'store']);
        Route::put('/workspaces/{workspace}/members/{user}', [MemberController::class, 'update']);
        Route::delete('/workspaces/{workspace}/members/{user}', [MemberController::class, 'destroy']);

        Route::get('/workspaces/{workspace}/invites', [InviteController::class, 'index']);
        Route::post('/workspaces/{workspace}/invites', [InviteController::class, 'store']);
        Route::delete('/workspaces/{workspace}/invites/{invite}', [InviteController::class, 'destroy']);
    });

    Route::post('/invites/{token}/accept', [InviteController::class, 'accept']);
});
