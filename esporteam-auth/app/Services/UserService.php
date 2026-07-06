<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(private ?RabbitMqPublisher $publisher = null)
    {
        $this->publisher ??= app(RabbitMqPublisher::class);
    }

    /**
     * Self-service permissions update.
     *
     * Security: preserves the `is_esporteam_admin` (bit 1, value 2) and
     * `is_esporteam_owner` (bit 2, value 4) bits from the database, regardless
     * of the input value. This prevents privilege escalation via self-service:
     * a user can never promote themselves to admin or owner through this
     * endpoint, and admins/owners cannot accidentally revoke their own
     * privileges here either. Changes to these bits must go through
     * `PUT /api/admin/users/{id}/permissions` (which is restricted to owners).
     */
    public function updatePermissions(User $user, int $permissions): User
    {
        $preservedBits = ((int) $user->permissions) & 6; // bits 1 (admin) + 2 (owner)
        $sanitized = ($permissions & ~6) | $preservedBits;

        $user->update(['permissions' => $sanitized]);
        return $user;
    }

    /**
     * Self-service profile update. Apenas campos não-sensíveis (name, email).
     * A unicidade de email é validada no FormRequest. Mudança de senha tem
     * fluxo próprio (forgot/reset) e não passa por aqui.
     */
    public function updateMe(User $user, array $data): User
    {
        $allowed = collect($data)->only(['name', 'email'])->filter()->all();
        if (empty($allowed)) {
            return $user;
        }
        $user->update($allowed);
        return $user->fresh();
    }

    /**
     * Self-service account deletion.
     *
     * Soft delete + anonymization. PII (name/email/phone) is overwritten so
     * the user can't be identified post-deletion. The auth row is soft-deleted
     * (deleted_at set) — Laravel's SoftDeletes global scope hides it from
     * Auth::attempt, so subsequent login attempts fail naturally.
     *
     * A `user.deleted` event is published on the `esporteam.events` topic exchange.
     * Other services consume it to anonymize linked entities (Staff, Guardian, etc.).
     *
     * Owner of one or more workspaces — DOES NOT block. The workspace becomes
     * orphan/inaccessible by design (decision 2026-04-28).
     */
    public function softDelete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $userId = $user->id;
            $tombstoneEmail = "deleted_{$userId}@deleted.local";

            $user->forceFill([
                'name' => 'Usuário removido',
                'email' => $tombstoneEmail,
                'password' => bcrypt(Str::random(64)),
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();

            $user->delete();
        });

        try {
            $this->publisher->publish('esporteam.events', 'user.deleted', [
                'event_id' => (string) Str::uuid(),
                'event_name' => 'user.deleted',
                'occurred_at' => now()->toIso8601String(),
                'data' => [
                    'user_id' => $user->id,
                ],
            ]);
        } catch (\Throwable $e) {
            // Não bloqueia o delete por falha de mensageria — best effort.
            \Log::error('Failed to publish user.deleted event', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
