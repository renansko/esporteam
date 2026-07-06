<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Gera um secret e recovery codes para o usuário.
     * NÃO ativa o 2FA — o usuário precisa confirmar com um código válido.
     */
    public function enable(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => null,
        ]);

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'esporteam'),
            $user->email,
            $secret,
        );

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Confirma o 2FA verificando o código TOTP.
     */
    public function confirm(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        if (!$this->verifyCode($user, $code)) {
            return false;
        }

        $user->update(['two_factor_confirmed_at' => now()]);

        return true;
    }

    /**
     * Desativa o 2FA do usuário.
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Verifica um código TOTP.
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        return $this->google2fa->verifyKey($user->two_factor_secret, $code);
    }

    /**
     * Verifica um recovery code e o invalida se correto.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $index = array_search($code, $codes);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $user->update(['two_factor_recovery_codes' => array_values($codes)]);

        return true;
    }

    /**
     * Verifica se o usuário tem 2FA ativo (secret + confirmado).
     */
    public function isEnabled(User $user): bool
    {
        return $user->two_factor_secret && $user->two_factor_confirmed_at;
    }

    private function generateRecoveryCodes(int $count = 8): array
    {
        return array_map(
            fn () => Str::upper(Str::random(4) . '-' . Str::random(4)),
            range(1, $count)
        );
    }
}
