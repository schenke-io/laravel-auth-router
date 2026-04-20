<?php

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\FacebookProvider;

it('can login with Facebook', function () {
    $this->app->config->set('services.facebook.client_id', 'facebook_client_id');
    $this->app->config->set('services.facebook.client_secret', 'facebook_client_secret');

    $redirectUrl = 'http://provider.auth.url';
    $redirectResponse = new RedirectResponse($redirectUrl);

    Socialite::shouldReceive('driver')->with('facebook')->andReturnSelf();
    Socialite::shouldReceive('redirect')->andReturn($redirectResponse);

    $provider = new FacebookProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $response = $provider->login($routerData);
    $this->assertEquals($redirectUrl, $response->getTargetUrl());
    $this->assertCount(0, $provider->errors());
});
