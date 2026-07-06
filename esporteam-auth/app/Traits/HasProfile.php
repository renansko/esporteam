<?php

namespace App\Traits;

use App\Enums\UserProfile;

trait HasProfile
{
    public function hasProfile(string|UserProfile $profile): bool
    {
        $profile = $profile instanceof UserProfile ? $profile->value : $profile;

        return $this->profile === $profile;
    }

    /**
     * @param  array<int, string|UserProfile>  $profiles
     */
    public function hasAnyProfile(array $profiles): bool
    {
        foreach ($profiles as $profile) {
            if ($this->hasProfile($profile)) {
                return true;
            }
        }

        return false;
    }

    public function profileLevel(): int
    {
        return UserProfile::tryFrom((string) $this->profile)?->level() ?? UserProfile::User->level();
    }

    public function isAdmin(): bool
    {
        return $this->hasProfile(UserProfile::Admin);
    }
}
