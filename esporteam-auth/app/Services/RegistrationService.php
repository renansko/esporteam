<?php

namespace App\Services;

use App\Enums\UserProfile;
use App\Models\User;

/**
 * @wiki app/brain/services/RegistrationService.md
 */
class RegistrationService
{
    /**
     * @wiki app/brain/functions/RegistrationService.md#createUser
     */
    public function createUser(array $data): User
    {
        $inviteToken = $data['invite_token'] ?? null;
        $registrationIntent = $data['registration_intent'] ?? 'participant';

        $data['permissions'] = $inviteToken ? 0 : 1;
        $data['profile'] = $registrationIntent === 'teacher'
            ? UserProfile::Teacher->value
            : UserProfile::User->value;

        unset($data['invite_token'], $data['registration_intent']);

        return User::create($data);
    }
}
