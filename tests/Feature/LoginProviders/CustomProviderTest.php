<?php

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\CustomProvider;

it('can login with Custom provider', function () {
    $this->app->config->set('services.custom.client_id', 'custom_client_id');
    $this->app->config->set('services.custom.client_secret', 'custom_client_secret');

    $redirectUrl = 'http://provider.auth.url';
    $redirectResponse = new RedirectResponse($redirectUrl);

    Socialite::shouldReceive('driver')->with('custom')->andReturnSelf();
    Socialite::shouldReceive('redirectUrl')->andReturnSelf();
    Socialite::shouldReceive('redirect')->andReturn($redirectResponse);

    $provider = new CustomProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $response = $provider->login($routerData);
    $this->assertEquals($redirectUrl, $response->getTargetUrl());
    $this->assertCount(0, $provider->errors());
});
