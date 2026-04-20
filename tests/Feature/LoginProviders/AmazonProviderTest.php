<?php

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\AmazonProvider;

it('can login with Amazon', function () {
    $this->app->config->set('services.amazon.client_id', 'amazon_client_id');
    $this->app->config->set('services.amazon.client_secret', 'amazon_client_secret');

    $redirectUrl = 'http://provider.auth.url';
    $redirectResponse = new RedirectResponse($redirectUrl);

    Socialite::shouldReceive('driver')->with('amazon')->andReturnSelf();
    Socialite::shouldReceive('redirect')->andReturn($redirectResponse);

    $provider = new AmazonProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $response = $provider->login($routerData);
    $this->assertEquals($redirectUrl, $response->getTargetUrl());
    $this->assertCount(0, $provider->errors());
});
