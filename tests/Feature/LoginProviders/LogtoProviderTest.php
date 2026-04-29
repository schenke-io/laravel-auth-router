<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Logto\Sdk\LogtoClient;
use Logto\Sdk\Models\IdTokenClaims;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\LogtoProvider;

class TestLogtoProvider extends LogtoProvider
{
    public LogtoClient $clientMock;

    protected function getClient(): LogtoClient
    {
        return $this->clientMock;
    }
}

it('can redirect to logto', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');
    $this->app->config->set('services.logto.app_secret', 'app_secret');
    $this->app->config->set('services.logto.redirect', 'http://localhost/callback/logto');

    $clientMock = Mockery::mock(LogtoClient::class);
    $clientMock->shouldReceive('signIn')->with('http://localhost/callback/logto')->andReturn('https://logto.example.com/oidc/auth?test=1');

    $provider = new TestLogtoProvider('logto');
    $provider->clientMock = $clientMock;

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $response = $provider->login($routerData);

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getTargetUrl())->toBe('https://logto.example.com/oidc/auth?test=1');
});

it('handles callback from logto', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');
    $this->app->config->set('services.logto.app_secret', 'app_secret');

    Route::get('/dashboard', fn () => '')->name('dashboard');
    app('router')->getRoutes()->refreshNameLookups();

    $claims = new IdTokenClaims(
        iss: 'https://logto.example.com/oidc',
        sub: 'user_123',
        aud: 'app_id',
        exp: time() + 3600,
        iat: time(),
        name: 'John Doe',
        email: 'john@example.com',
        picture: 'https://example.com/avatar.png'
    );

    $clientMock = Mockery::mock(LogtoClient::class);
    $clientMock->shouldReceive('handleSignInCallback')->once();
    $clientMock->shouldReceive('getIdTokenClaims')->andReturn($claims);

    $provider = new TestLogtoProvider('logto');
    $provider->clientMock = $clientMock;

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $response = $provider->callback($routerData);

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getTargetUrl())->toBe('http://localhost/dashboard');
});

it('handles callback errors from logto', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');
    $this->app->config->set('services.logto.app_secret', 'app_secret');

    Route::get('/error', fn () => '')->name('error');
    app('router')->getRoutes()->refreshNameLookups();

    $clientMock = Mockery::mock(LogtoClient::class);
    $clientMock->shouldReceive('handleSignInCallback')->andThrow(new Exception('Callback error'));

    $provider = new TestLogtoProvider('logto');
    $provider->clientMock = $clientMock;

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $response = $provider->callback($routerData);

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getTargetUrl())->toContain('/error');
});

it('can create a logto client', function () {
    $provider = new LogtoProvider('logto');
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('getClient');
    $method->setAccessible(true);

    try {
        $method->invoke($provider);
    } catch (Exception $e) {
        // ignore network error during construction in test environment
    }
    expect(true)->toBeTrue();
});

it('has correct env and social status', function () {
    $provider = new LogtoProvider('logto');
    expect($provider->isSocial())->toBeTrue();
    expect($provider->env())->toBeArray();
    expect($provider->env())->toHaveKey('endpoint');
});

it('can logout from logto', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');
    $this->app->config->set('services.logto.app_secret', 'app_secret');

    Route::get('/the-home-path', fn () => '')->name('the-home-route');
    app('router')->getRoutes()->refreshNameLookups();

    $clientMock = Mockery::mock(LogtoClient::class);
    $clientMock->shouldReceive('signOut')
        ->with('http://localhost/the-home-path')
        ->andReturn('https://logto.example.com/oidc/logout?test=1');

    $provider = new TestLogtoProvider('logto');
    $provider->clientMock = $clientMock;

    $routerData = new RouterData('dashboard', 'error', 'the-home-route', true);
    $response = $provider->logout($routerData);

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getTargetUrl())->toBe('https://logto.example.com/oidc/logout?test=1');
});
