<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\GoogleProvider;
use Workbench\App\Models\User;

it('logs login start', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');

    Log::shouldReceive('channel')
        ->with('test-channel')
        ->andReturnSelf();

    Log::shouldReceive('info')
        ->once()
        ->with('AuthRouter login start', \Mockery::on(fn ($data) => $data['provider'] === 'google'));

    // Socialite mocks
    $redirectUrl = 'http://provider.auth.url';
    $redirectResponse = new RedirectResponse($redirectUrl);
    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('redirectUrl')->andReturnSelf();
    Socialite::shouldReceive('redirect')->andReturn($redirectResponse);

    $provider = new GoogleProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true, false, '', null, null, [], false, 'test-channel');
    $provider->login($routerData);
});

it('logs callback start and success', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');
    $this->app->config->set('auth.providers.users.model', User::class);

    Route::get('/home', fn () => '')->name('home');
    Route::get('/dashboard', fn () => '')->name('dashboard');
    app('router')->getRoutes()->refreshNameLookups();

    Log::shouldReceive('channel')
        ->with('test-channel')
        ->andReturnSelf();

    Log::shouldReceive('info')
        ->once()
        ->with('AuthRouter callback start', \Mockery::on(fn ($data) => $data['provider'] === 'google'));

    Log::shouldReceive('info')
        ->once()
        ->with('AuthRouter success', \Mockery::on(fn ($data) => $data['provider'] === 'google' && $data['email'] === 'test@example.com'));

    // Socialite mocks
    $socialiteUserMock = \Mockery::mock(SocialiteUser::class);
    $socialiteUserMock->shouldReceive('getId')->andReturn('123');
    $socialiteUserMock->shouldReceive('getName')->andReturn('Test User');
    $socialiteUserMock->shouldReceive('getEmail')->andReturn('test@example.com');
    $socialiteUserMock->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('redirectUrl')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUserMock);

    $provider = new GoogleProvider;
    $routerData = new RouterData('dashboard', 'home', 'home', true, false, '', null, null, [], false, 'test-channel');
    $provider->callback($routerData);
});

it('logs errors', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');

    Route::get('/error', fn () => '')->name('error');
    app('router')->getRoutes()->refreshNameLookups();

    Log::shouldReceive('channel')
        ->with('test-channel')
        ->andReturnSelf();

    Log::shouldReceive('info')
        ->once()
        ->with('AuthRouter callback start', \Mockery::on(fn ($data) => $data['provider'] === 'google'));

    Log::shouldReceive('error')
        ->once()
        ->with('AuthRouter error', \Mockery::on(fn ($data) => $data['type'] === 'LocalAuth'));

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('redirectUrl')->andReturnSelf();
    Socialite::shouldReceive('user')->andThrow(new \Exception('Test Error'));

    $provider = new GoogleProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true, false, '', null, null, [], false, 'test-channel');
    $provider->callback($routerData);
});
