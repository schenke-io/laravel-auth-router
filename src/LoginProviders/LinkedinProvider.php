<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

use Illuminate\Support\Facades\Config;

/**
 * Social login with LinkedIn
 *
 * Go to Developer Portal, sign in, navigate to "My Apps," create an Application, find Client ID and Secret under "Authentication Keys" in "Authentication" settings.
 *
 * @link https://developer.linkedin.com/
 */
class LinkedinProvider extends SocialiteProvider
{
    protected function beforeRequest(): void
    {
        Config::set('services.linkedin-openid', Config::get('services.linkedin'));
    }

    protected function getSocialiteDriverName(): string
    {
        return 'linkedin-openid';
    }

    protected function getScopes(): array
    {
        return ['openid', 'profile', 'email'];
    }
}
