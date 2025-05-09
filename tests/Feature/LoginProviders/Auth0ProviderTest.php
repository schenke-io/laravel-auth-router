<?php

use Auth0\SDK\Auth0;
use Auth0\SDK\Exception\NetworkException;
use Auth0\SDK\Exception\StateException;
use Illuminate\Http\Request;
use SchenkeIo\LaravelAuthRouter\LoginProviders\Auth0Provider;
use Workbench\App\Models\User;

it('can redirect to login page without hint', function () {
    $redirectUri = 'http://auth0.com/auth';
    $provider = new Auth0Provider;
    $request = new Request(['hint' => null]);

    $mockAuth0 = $this->mock('alias:'.Auth0::class);
    $mockAuth0->shouldReceive('login')->andReturn($redirectUri);

    $response = $provider->login($mockAuth0, $request, $redirectUri);
    $this->assertEquals($redirectUri, $response->getTargetUrl());
});

it('can redirect to login page with hint', function () {
    $redirectUri = 'http://localhost/';
    $redirectUriEnd = 'http://localhost/?login_hint=test%40example.com';

    $mockAuth0 = $this->mock('alias:'.Auth0::class);
    $mockAuth0->shouldReceive('login')->andReturn($redirectUriEnd);

    $request = new Request(['hint' => 'test@example.com']);
    $response = (new Auth0Provider)->login($mockAuth0, $request, $redirectUri);
    $this->assertEquals($redirectUriEnd, $response->getTargetUrl());
});

it('has 4 env variables', function () {
    $provider = new Auth0Provider;
    $this->assertEquals(4, count($provider->env()));
});

it('can authenticate with auth0', function () {
    $this->app->config->set('auth.providers.users.model', User::class);

    $request = new Request(['state' => 'state', 'code' => 'code']);
    $routerData = getRouterData(true);

    $auth0User = [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'picture' => 'https://example.com/john-doe.jpg',
        'other' => 123444,
    ];

    $mockAuth0 = $this->mock('alias:'.Auth0::class);
    $mockAuth0->shouldReceive('exchange')->once();
    $mockAuth0->shouldReceive('getUser')->once()->andReturn($auth0User);

    $response = (new Auth0Provider)->callback($mockAuth0, $request, $routerData);
    $this->assertEquals('http://localhost/route-success', $response->getTargetUrl());
});

it('handles the error code in callback', function () {
    $request = new Request(['error' => 'something went wrong']);
    $routerData = getRouterData(true);

    $mockAuth0 = $this->mock('alias:'.Auth0::class);

    $response = (new Auth0Provider)->callback($mockAuth0, $request, $routerData);
    $this->assertEquals('http://localhost/route-error', $response->getTargetUrl());
    $this->assertEquals('RemoteAuth', customErrorType($response));
});

it('fails to authenticate', function () {
    $request = new Request(['state' => 'state', 'code' => 'code']);
    $routerData = getRouterData(true);

    $mockAuth0 = $this->mock('alias:'.Auth0::class);
    $mockAuth0->shouldReceive('exchange')->once();
    $mockAuth0->shouldReceive('getUser')->once()->andReturn(null);

    $response = (new Auth0Provider)->callback($mockAuth0, $request, $routerData);
    $this->assertEquals('LocalAuth', customErrorType($response));
});

it('can handle a network exception', function () {
    $request = new Request(['state' => 'state', 'code' => 'code']);
    $routerData = getRouterData(true);

    $mockAuth0 = $this->mock('alias:'.Auth0::class);
    $mockAuth0->shouldReceive('exchange')->andThrowExceptions([new NetworkException]);

    $response = (new Auth0Provider)->callback($mockAuth0, $request, $routerData);
    $this->assertEquals('Network', customErrorType($response));
});

it('can handle a state exception', function () {
    $request = new Request(['state' => 'state', 'code' => 'code']);
    $routerData = getRouterData(true);

    $mockAuth0 = $this->mock('alias:'.Auth0::class);
    $mockAuth0->shouldReceive('exchange')->andThrowExceptions([new StateException]);

    $response = (new Auth0Provider)->callback($mockAuth0, $request, $routerData);
    $this->assertEquals('State', customErrorType($response));
});

it('can handle an invalid request', function () {
    $request = new Request(['wrong' => 'wrong']);
    $routerData = getRouterData(true);

    $mockAuth0 = $this->mock('alias:'.Auth0::class);

    $response = (new Auth0Provider)->callback($mockAuth0, $request, $routerData);
    $this->assertEquals('InvalidRequest', customErrorType($response));
});
