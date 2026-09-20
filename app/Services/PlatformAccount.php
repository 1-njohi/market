<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

/**
 * Resolves the platform's User account from config, caching the result
 * for the life of the process.
 *
 * Registered as a singleton in AppServiceProvider. Tests can override
 * the resolved user via setUser() without touching config.
 */
class PlatformAccount
{
    private ?User $user = null;

    public function user(): User
    {
        return $this->user ??= $this->resolve();
    }

    /**
     * Override the resolved user (for tests).
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    private function resolve(): User
    {
        $email = config('services.betslip_pirates.platform_user_email');

        if (!is_string($email) || $email === '') {
            throw new RuntimeException(
                'Platform user is not configured. Set '
                . 'services.betslip_pirates.platform_user_email.'
            );
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new RuntimeException(
                "Platform user with email {$email} does not exist. "
                . 'Has PlatformUserSeeder been run?'
            );
        }

        return $user;
    }
}