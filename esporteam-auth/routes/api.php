<?php

use App\Http\Controllers\Admin\AdminAuditController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\WorkspaceTokenController;
use App\Http\Controllers\Service\ServiceAuditController;
use App\Http\Controllers\ServiceUserController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'sha'    => config('app.git_sha'),
]));

Route::middleware(['service.token', 'throttle:1000,1'])->prefix('service')->group(function () {
    Route::get('/users/by-email', [ServiceUserController::class, 'findByEmail']);
    Route::post('/users/bulk-lookup', [ServiceUserController::class, 'bulkLookup']);
    Route::post('/users/grant-permissions', [ServiceUserController::class, 'grantPermissions']);
    Route::post('/audit', [ServiceAuditController::class, 'store']);
});

// Rotas públicas (rate limited)
Route::prefix('auth')->middleware('throttle:300,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    Route::post('/password/forgot', [PasswordResetController::class, 'forgot']);
    Route::post('/password/reset',  [PasswordResetController::class, 'reset']);
});

// Rotas protegidas
Route::middleware(['auth.jwt', 'throttle:600,1'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/me', [UserController::class, 'updateMe']);
    Route::delete('/me', [UserController::class, 'deleteMe']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/workspace/select', [WorkspaceTokenController::class, 'select']);
    Route::put('/users/{id}/permissions', [UserController::class, 'updatePermissions']);

    // 2FA
    Route::prefix('2fa')->group(function () {
        Route::get('/status', [TwoFactorController::class, 'status']);
        Route::post('/enable', [TwoFactorController::class, 'enable']);
        Route::post('/confirm', [TwoFactorController::class, 'confirm']);
        Route::delete('/disable', [TwoFactorController::class, 'disable']);
    });
});

// Rotas de super admin (esporteam admins)
Route::middleware(['auth.jwt', 'esporteam.admin', 'throttle:600,1'])->prefix('admin')->group(function () {
    Route::post('/impersonate', [ImpersonationController::class, 'impersonate']);
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::get('/audit', [AdminAuditController::class, 'index']);
});

// Rotas restritas ao dono da plataforma esporteam (bit 2 do bitmask global)
Route::middleware(['auth.jwt', 'esporteam.owner', 'throttle:600,1'])->prefix('admin')->group(function () {
    Route::put('/users/{id}/permissions', [AdminUserController::class, 'updatePermissions']);
});
