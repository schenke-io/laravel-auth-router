<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use ArchTech\Enums\From;
use SchenkeIo\LaravelAuthRouter\LoginProviders\AmazonProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\Auth0Provider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\FacebookProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\GoogleProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\LinkedInProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\MicrosoftProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\PaypalProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\StripeProvider;

/**
 * all existing auth services
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

    public static function get(string $provider): ?Service
    {
        return self::tryFromName(strtolower($provider));
    }

    public function provider(): BaseProvider
    {
        return match ($this) {
            self::amazon => new AmazonProvider,
            self::google => new GoogleProvider,
            self::linkedin => new LinkedInProvider,
            self::microsoft => new MicrosoftProvider,
            self::paypal => new PaypalProvider,
            self::auth0 => new Auth0Provider,
            self::facebook => new FacebookProvider,
            self::stripe => new StripeProvider,
        };
    }
}
