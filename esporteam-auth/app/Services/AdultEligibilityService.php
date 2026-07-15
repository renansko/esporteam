<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * @wiki app/brain/services/AdultEligibilityService.md
 */
class AdultEligibilityService
{
    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @wiki app/brain/functions/AdultEligibilityService.md#declare
     */
    public function declare(User $user, array $data): User
    {
        $birthDate = CarbonImmutable::parse($data['birth_date'])->startOfDay();
        $isAdult = $birthDate->addYears(18)->lessThanOrEqualTo(today());

        if (! $isAdult) {
            throw ValidationException::withMessages([
                'birth_date' => 'Adult eligibility requires an age of at least 18 years.',
            ]);
        }

        $changed = $user->birth_date?->toDateString() !== $birthDate->toDateString()
            || ! $user->is_adult;

        $user->forceFill([
            'birth_date' => $birthDate->toDateString(),
            'adult_attested_at' => now(),
            'is_adult' => true,
            'tokens_revoked_at' => $changed ? now() : $user->tokens_revoked_at,
        ])->save();

        $this->audit->log($user->id, $user->email, 'declare_adult_eligibility', 'user', $user->id, [], ['is_adult' => true]);

        return $user->fresh();
    }
}
