<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Console\Command;

class GrantEsporteamOwner extends Command
{
    protected $signature = 'esporteam:grant-owner {email}';

    protected $description = 'Grant esporteam owner role to a user (sets permissions = 7: owner + admin + can_create_workspace).';

    public function handle(AuditLogService $auditLog): int
    {
        $email = (string) $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email [{$email}] not found.");
            return self::FAILURE;
        }

        $old = (int) $user->permissions;
        // OR in bits 0 (can_create_workspace), 1 (admin) and 2 (owner).
        $new = $old | 7;

        $user->update(['permissions' => $new]);

        $auditLog->log(
            0,
            'console',
            'grant_esporteam_owner',
            'user',
            (int) $user->id,
            ['target_email' => $user->email, 'old_permissions' => $old],
            ['old' => $old, 'new' => $new],
        );

        $this->info("Esporteam owner role granted to {$user->email} (id={$user->id}). Permissions: {$old} -> {$new}.");

        return self::SUCCESS;
    }
}
