<?php

namespace App\Enums;

enum SportSessionEntryMode: string
{
    case InviteOnly = 'convite';
    case PublicDirect = 'publica_direta';
    case PublicApproval = 'publica_aprovacao';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function requiresApproval(): bool
    {
        return $this === self::PublicApproval;
    }

    public function nextAction(): string
    {
        return match ($this) {
            self::PublicDirect => 'entrar',
            self::PublicApproval => 'pedir_vaga',
            self::InviteOnly => 'indisponivel',
        };
    }
}
