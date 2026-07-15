<?php

namespace App\Services;

use App\Enums\UserProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @wiki app/brain/services/RegistrationService.md
 */
class RegistrationService
{
    public function __construct(private readonly AdultEligibilityService $adultEligibility) {}

    /**
     * @wiki app/brain/functions/RegistrationService.md#createUser
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $birthDate = $data['birth_date'] ?? null;
        $inviteToken = $data['invite_token'] ?? null;
        $registrationIntent = $data['registration_intent'] ?? 'participant';

        $data['permissions'] = $inviteToken ? 0 : 1;
        $data['profile'] = $registrationIntent === 'teacher'
            ? UserProfile::Teacher->value
            : UserProfile::User->value;

        unset($data['invite_token'], $data['registration_intent'], $data['birth_date'], $data['adult_attestation']);

            $user = User::create($data);

            return $birthDate === null
                ? $user
                : $this->adultEligibility->declare($user, ['birth_date' => $birthDate]);
        });
    }
}
