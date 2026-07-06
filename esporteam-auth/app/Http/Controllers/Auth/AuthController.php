<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Enums\UserProfile;
use App\Models\User;
use App\Services\JwtService;
use App\Services\RabbitMqPublisher;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function __construct(
        private JwtService $jwt,
        private RabbitMqPublisher $publisher,
        private TwoFactorService $twoFactor,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $inviteToken = $data['invite_token'] ?? null;

        if ($inviteToken) {
            $data['permissions'] = 0;
        } else {
            $data['permissions'] = 1; // can_create_workspace
        }
        $data['profile'] = UserProfile::User->value;

        unset($data['invite_token']);

        $user = User::create($data);
        $token = $this->jwt->encode($user->toArray());

        if ($inviteToken) {
            $workspaceResponse = Http::timeout(10)
                ->withHeader('X-Service-Token', config('services.school.token'))
                ->post(config('services.workspace.url') . '/api/service/invites/' . $inviteToken . '/accept', [
                    'user_id' => $user->id,
                ]);

            if (!$workspaceResponse->successful()) {
                \Log::error('Failed to accept workspace invite during registration', [
                    'user_id' => $user->id,
                    'invite_token' => $inviteToken,
                    'status' => $workspaceResponse->status(),
                    'body' => $workspaceResponse->json(),
                ]);
            }

            try {
                $this->publisher->publish('esporteam.events', 'user.registered', [
                    'user_id'      => $user->id,
                    'invite_token' => $inviteToken,
                ]);
            } catch (\Throwable $e) {
                \Log::error('Failed to publish user.registered event', [
                    'user_id' => $user->id,
                    'invite_token' => $inviteToken,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->createdResponse([
            'user'  => new UserResource($user),
            'token' => $token,
        ], __('messages.auth.register'));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse(__('messages.auth.login_failed'), null, 401);
        }

        $user = Auth::user();

        // Se 2FA está habilitado globalmente e ativo para o usuário
        if (config('auth.two_factor_enabled') && $this->twoFactor->isEnabled($user)) {
            $code = $request->input('two_factor_code');

            if (!$code) {
                return $this->successResponse([
                    'two_factor_required' => true,
                ], 'Código de autenticação de dois fatores necessário.');
            }

            $valid = $this->twoFactor->verifyCode($user, $code)
                || $this->twoFactor->verifyRecoveryCode($user, $code);

            if (!$valid) {
                return $this->errorResponse('Código de dois fatores inválido.', null, 422);
            }
        }

        $token = $this->jwt->encode($user->toArray());

        return $this->successResponse([
            'user'  => new UserResource($user),
            'token' => $token,
        ], __('messages.auth.login'));
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UserResource($request->user()),
            __('messages.auth.me')
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill(['tokens_revoked_at' => now()])->save();

        return $this->successResponse(null, 'Sessão encerrada.');
    }
}
