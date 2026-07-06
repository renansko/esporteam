<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactor,
    ) {}

    /**
     * POST /api/2fa/enable
     * Gera secret + QR code. Usuário precisa confirmar com /api/2fa/confirm.
     */
    public function enable(Request $request): JsonResponse
    {
        if (!config('auth.two_factor_enabled')) {
            return $this->errorResponse('Autenticação de dois fatores está desabilitada.', null, 403);
        }

        $user = $request->user();

        if ($this->twoFactor->isEnabled($user)) {
            return $this->errorResponse('Autenticação de dois fatores já está ativa.', null, 422);
        }

        $data = $this->twoFactor->enable($user);

        return $this->successResponse($data, 'QR code gerado. Escaneie e confirme com o código.');
    }

    /**
     * POST /api/2fa/confirm
     * Confirma o 2FA com um código TOTP válido.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $user = $request->user();

        if ($this->twoFactor->isEnabled($user)) {
            return $this->errorResponse('Autenticação de dois fatores já está confirmada.', null, 422);
        }

        if (!$this->twoFactor->confirm($user, $request->input('code'))) {
            return $this->errorResponse('Código inválido.', null, 422);
        }

        return $this->successResponse(null, 'Autenticação de dois fatores ativada com sucesso.');
    }

    /**
     * DELETE /api/2fa/disable
     * Desativa o 2FA. Requer código TOTP ou recovery code para confirmar.
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();

        if (!$this->twoFactor->isEnabled($user)) {
            return $this->errorResponse('Autenticação de dois fatores não está ativa.', null, 422);
        }

        $code = $request->input('code');
        $valid = $this->twoFactor->verifyCode($user, $code)
            || $this->twoFactor->verifyRecoveryCode($user, $code);

        if (!$valid) {
            return $this->errorResponse('Código inválido.', null, 422);
        }

        $this->twoFactor->disable($user);

        return $this->successResponse(null, 'Autenticação de dois fatores desativada.');
    }

    /**
     * GET /api/2fa/status
     * Retorna se o 2FA está ativo para o usuário.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'enabled' => $this->twoFactor->isEnabled($user),
        ], 'Status do 2FA.');
    }
}
