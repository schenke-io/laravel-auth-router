<?php

use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Service;
use SchenkeIo\LaravelAuthRouter\LoginProviders\AmazonProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\Auth0Provider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\GoogleProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\LinkedInProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\MicrosoftProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\PaypalProvider;

it('returns the correct provider for each enum case', function () {
    // Test Amazon case
    $provider = Service::amazon->provider();
    expect($provider)->toBeInstanceOf(AmazonProvider::class)
        ->and($provider)->toBeInstanceOf(BaseProvider::class);
    // Optional: Check against the base type

    // Test Google case
    $provider = Service::google->provider();
    expect($provider)->toBeInstanceOf(GoogleProvider::class)
        ->and($provider)->toBeInstanceOf(BaseProvider::class);

    // Test LinkedIn case
    $provider = Service::linkedin->provider();
    expect($provider)->toBeInstanceOf(LinkedInProvider::class)
        ->and($provider)->toBeInstanceOf(BaseProvider::class);

    // Test Microsoft case
    $provider = Service::microsoft->provider();
    expect($provider)->toBeInstanceOf(MicrosoftProvider::class)
        ->and($provider)->toBeInstanceOf(BaseProvider::class);

    // Test Paypal case
    $provider = Service::paypal->provider();
    expect($provider)->toBeInstanceOf(PaypalProvider::class)
        ->and($provider)->toBeInstanceOf(BaseProvider::class);

    // Test Auth0 case
    $provider = Service::auth0->provider();
    expect($provider)->toBeInstanceOf(Auth0Provider::class)
        ->and($provider)->toBeInstanceOf(BaseProvider::class);
});
