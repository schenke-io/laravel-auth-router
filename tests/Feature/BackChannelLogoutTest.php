<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Events\BackChannelLogoutEvent;
use SchenkeIo\LaravelAuthRouter\LoginProviders\Auth0Provider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\LogtoProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\UnknownBaseProvider;

it('registers the back-channel logout route', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');
    $this->app->config->set('services.logto.app_secret', 'app_secret');

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $provider = new LogtoProvider('logto');

    // The route should be registered by BaseProvider::registerRoutes
    $provider->registerRoutes($routerData, ['web']);
    app('router')->getRoutes()->refreshNameLookups();

    expect(Route::has('logout.logto.back-channel'))->toBeTrue();
});

it('returns 501 if back-channel logout is not implemented', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');
    $this->app->config->set('services.logto.app_secret', 'app_secret');

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $provider = new LogtoProvider('logto');
    $provider->registerRoutes($routerData, ['web']);
    app('router')->getRoutes()->refreshNameLookups();

    $response = $this->post(route('logout.logto.back-channel'));

    expect($response->status())->toBe(400);
    expect($response->getContent())->toBe('Missing logout_token');
});

it('validates a correct logout token', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');
    $this->app->config->set('services.logto.app_secret', 'app_secret');

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $provider = new LogtoProvider('logto');
    $provider->registerRoutes($routerData, ['web']);
    app('router')->getRoutes()->refreshNameLookups();

    $config = Configuration::forSymmetricSigner(new Sha256, InMemory::plainText('a-very-long-secret-key-that-is-at-least-256-bits-long'));
    $token = $config->builder()
        ->issuedBy('https://logto.example.com/oidc')
        ->permittedFor('app_id')
        ->withClaim('events', ['http://schemas.openid.net/event/backchannel-logout' => (object) []])
        ->issuedAt(new DateTimeImmutable)
        ->relatedTo('user_123')
        ->getToken($config->signer(), $config->signingKey());

    $response = $this->post(route('logout.logto.back-channel'), [
        'logout_token' => $token->toString(),
    ]);

    expect($response->status())->toBe(200);
    expect($response->getContent())->toBe('OK');
});

it('dispatches BackChannelLogoutEvent on success', function () {
    Event::fake();

    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $provider = new LogtoProvider('logto');
    $provider->registerRoutes($routerData, ['web']);
    app('router')->getRoutes()->refreshNameLookups();

    $config = Configuration::forSymmetricSigner(new Sha256, InMemory::plainText('a-very-long-secret-key-that-is-at-least-256-bits-long'));
    $token = $config->builder()
        ->issuedBy('https://logto.example.com/oidc')
        ->permittedFor('app_id')
        ->withClaim('events', ['http://schemas.openid.net/event/backchannel-logout' => (object) []])
        ->issuedAt(new DateTimeImmutable)
        ->relatedTo('user_123')
        ->withClaim('sid', 'session_abc')
        ->getToken($config->signer(), $config->signingKey());

    $this->post(route('logout.logto.back-channel'), [
        'logout_token' => $token->toString(),
    ]);

    Event::assertDispatched(BackChannelLogoutEvent::class, function ($event) {
        return $event->provider === 'logto' &&
               $event->sub === 'user_123' &&
               $event->sid === 'session_abc';
    });
});

it('rejects a token with nonce', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $provider = new LogtoProvider('logto');
    $provider->registerRoutes($routerData, ['web']);
    app('router')->getRoutes()->refreshNameLookups();

    $config = Configuration::forSymmetricSigner(new Sha256, InMemory::plainText('a-very-long-secret-key-that-is-at-least-256-bits-long'));
    $token = $config->builder()
        ->withClaim('nonce', 'some_nonce')
        ->getToken($config->signer(), $config->signingKey());

    $response = $this->post(route('logout.logto.back-channel'), [
        'logout_token' => $token->toString(),
    ]);

    expect($response->status())->toBe(400);
    expect($response->getContent())->toBe('Token contains nonce');
});

it('rejects a token without logout event', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $provider = new LogtoProvider('logto');
    $provider->registerRoutes($routerData, ['web']);
    app('router')->getRoutes()->refreshNameLookups();

    $config = Configuration::forSymmetricSigner(new Sha256, InMemory::plainText('a-very-long-secret-key-that-is-at-least-256-bits-long'));
    $token = $config->builder()
        ->getToken($config->signer(), $config->signingKey());

    $response = $this->post(route('logout.logto.back-channel'), [
        'logout_token' => $token->toString(),
    ]);

    expect($response->status())->toBe(400);
    expect($response->getContent())->toBe('Missing backchannel-logout event');
});

it('rejects a token without iat', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $provider = new LogtoProvider('logto');
    $provider->registerRoutes($routerData, ['web']);
    app('router')->getRoutes()->refreshNameLookups();

    $config = Configuration::forSymmetricSigner(new Sha256, InMemory::plainText('a-very-long-secret-key-that-is-at-least-256-bits-long'));
    $token = $config->builder()
        ->withClaim('events', ['http://schemas.openid.net/event/backchannel-logout' => (object) []])
        ->getToken($config->signer(), $config->signingKey());

    $response = $this->post(route('logout.logto.back-channel'), [
        'logout_token' => $token->toString(),
    ]);

    expect($response->status())->toBe(400);
    expect($response->getContent())->toBe('Missing iat claim');
});

it('rejects a token without sub or sid', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $provider = new LogtoProvider('logto');
    $provider->registerRoutes($routerData, ['web']);
    app('router')->getRoutes()->refreshNameLookups();

    $config = Configuration::forSymmetricSigner(new Sha256, InMemory::plainText('a-very-long-secret-key-that-is-at-least-256-bits-long'));
    $token = $config->builder()
        ->withClaim('events', ['http://schemas.openid.net/event/backchannel-logout' => (object) []])
        ->issuedAt(new DateTimeImmutable)
        ->getToken($config->signer(), $config->signingKey());

    $response = $this->post(route('logout.logto.back-channel'), [
        'logout_token' => $token->toString(),
    ]);

    expect($response->status())->toBe(400);
    expect($response->getContent())->toBe('Missing sub or sid');
});

it('rejects a token with invalid issuer', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $provider = new LogtoProvider('logto');
    $provider->registerRoutes($routerData, ['web']);
    app('router')->getRoutes()->refreshNameLookups();

    $config = Configuration::forSymmetricSigner(new Sha256, InMemory::plainText('a-very-long-secret-key-that-is-at-least-256-bits-long'));
    $token = $config->builder()
        ->issuedBy('https://wrong.example.com')
        ->withClaim('events', ['http://schemas.openid.net/event/backchannel-logout' => (object) []])
        ->issuedAt(new DateTimeImmutable)
        ->relatedTo('user_123')
        ->getToken($config->signer(), $config->signingKey());

    $response = $this->post(route('logout.logto.back-channel'), [
        'logout_token' => $token->toString(),
    ]);

    expect($response->status())->toBe(400);
    expect($response->getContent())->toBe('Invalid issuer');
});

it('rejects a token with invalid audience', function () {
    $this->app->config->set('services.logto.endpoint', 'https://logto.example.com');
    $this->app->config->set('services.logto.app_id', 'app_id');

    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $provider = new LogtoProvider('logto');
    $provider->registerRoutes($routerData, ['web']);
    app('router')->getRoutes()->refreshNameLookups();

    $config = Configuration::forSymmetricSigner(new Sha256, InMemory::plainText('a-very-long-secret-key-that-is-at-least-256-bits-long'));
    $token = $config->builder()
        ->issuedBy('https://logto.example.com/oidc')
        ->permittedFor('wrong_app_id')
        ->withClaim('events', ['http://schemas.openid.net/event/backchannel-logout' => (object) []])
        ->issuedAt(new DateTimeImmutable)
        ->relatedTo('user_123')
        ->getToken($config->signer(), $config->signingKey());

    $response = $this->post(route('logout.logto.back-channel'), [
        'logout_token' => $token->toString(),
    ]);

    expect($response->status())->toBe(400);
    expect($response->getContent())->toBe('Invalid audience');
});

it('handles invalid token strings in catch block', function () {
    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $provider = new LogtoProvider('logto');
    $provider->registerRoutes($routerData, ['web']);
    app('router')->getRoutes()->refreshNameLookups();

    $response = $this->post(route('logout.logto.back-channel'), [
        'logout_token' => 'invalid-token-string',
    ]);

    expect($response->status())->toBe(400);
    expect($response->getContent())->toStartWith('Invalid token: ');
});

it('validates Auth0 issuer', function () {
    $this->app->config->set('services.auth0.domain', 'auth0.example.com');
    $provider = new Auth0Provider('auth0');
    expect($provider->getIssuer())->toBe('https://auth0.example.com/');

    $this->app->config->set('services.auth0.domain', null);
    expect($provider->getIssuer())->toBeNull();
});

it('covers UnknownBaseProvider issuer', function () {
    $provider = new UnknownBaseProvider('unknown');
    expect($provider->getIssuer())->toBeNull();
});

it('rejects invalid token format using mock', function () {
    $routerData = new RouterData('dashboard', 'error', 'home', true);

    $provider = Mockery::mock(LogtoProvider::class)->makePartial();
    $provider->shouldAllowMockingProtectedMethods();
    $provider->shouldReceive('valid')->andReturn(true);
    $provider->__construct('logto');

    $token = Mockery::mock(Token::class);
    $provider->shouldReceive('parseToken')->andReturn($token);

    $request = Request::create('/logout', 'POST', ['logout_token' => 'some-token']);

    $response = $provider->backChannelLogout($request, $routerData);

    expect($response->status())->toBe(400);
    expect($response->getContent())->toBe('Invalid token format');
});
