<?php

namespace SchenkeIo\LaravelAuthRouter\Enums;

use ArchTech\Enums\From;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\AmazonProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\AppleProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\Auth0Provider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\CustomProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\FacebookProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\GoogleProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\LinkedinProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\LogtoProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\MicrosoftProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\PasskeyProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\PaypalProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\StripeProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\WhatsappProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\WorkosProvider;

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
        return match ($this) {
            self::amazon => new AmazonProvider($this->name),
            self::google => new GoogleProvider($this->name),
            self::linkedin => new LinkedinProvider($this->name),
            self::microsoft => new MicrosoftProvider($this->name),
            self::paypal => new PaypalProvider($this->name),
            self::auth0 => new Auth0Provider($this->name),
            self::facebook => new FacebookProvider($this->name),
            self::stripe => new StripeProvider($this->name),
            self::whatsapp => new WhatsappProvider($this->name),
            self::apple => new AppleProvider($this->name),
            self::custom => new CustomProvider($this->name),
            self::workos => new WorkosProvider($this->name),
            self::logto => new LogtoProvider($this->name),
            self::passkey => new PasskeyProvider($this->name),
        };
    }
}
