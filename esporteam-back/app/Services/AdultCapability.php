<?php

namespace App\Services;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * @wiki app/brain/services/AdultCapability.md
 */
class AdultCapability
{
    /**
     * @wiki app/brain/functions/AdultCapability.md#assertAllowed
     */
    public function assertAllowed(object $user): void
    {
        if (($user->is_adult ?? false) !== true) {
            throw new AuthorizationException('adult_eligibility_required');
        }
    }
}
