<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use ArchTech\Enums\From;

/**
 * All supported authentication services.
 *
 * This enum lists all the third-party and internal authentication
 * services supported by the package.
 */
enum Service
{
    use From;

    case amazon;
    case google;
    case linkedin;
    case microsoft;
    case paypal;
    case auth0;
    case facebook;
    case stripe;
    case whatsapp;
    case apple;
    case custom;
    case workos;
    case logto;
    case passkey;

    /**
     * Get a Service enum instance from a provider string.
     *
     * This method handles case-insensitivity and underscores to find
     * a matching service.
     *
     * @param  string  $provider  The provider name (e.g., 'Google', 'workos_google').
     * @return Service|null The matching Service enum, or null if not found.
     */
    public static function get(string $provider): ?Service
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            foreach (self::cases() as $case) {
                $map[str_replace('_', '', strtolower($case->name))] = $case;
            }
        }
        $provider = str_replace('_', '', strtolower($provider));

        return $map[$provider] ?? null;
    }

    /**
     * Get the provider implementation instance for this service.
     *
     * @return BaseProvider The specific provider class instance.
     */
    public function provider(): BaseProvider
    {
        $className = 'SchenkeIo\\LaravelAuthRouter\\LoginProviders\\'.ucfirst($this->name).'Provider';

        return new $className;
    }
}
