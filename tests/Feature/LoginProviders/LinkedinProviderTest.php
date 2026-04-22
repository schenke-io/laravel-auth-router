<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\LinkedinProvider;
use Workbench\App\Models\User;

it('redirects to linkedin for login', function () {
    $this->app->config->set('services.linkedin.client_id', 'linkedin_client_id');
    $this->app->config->set('services.linkedin.client_secret', 'linkedin_client_secret');

    $redirectUrl = 'http://provider.auth.url';
    $redirectResponse = new RedirectResponse($redirectUrl);

    Socialite::shouldReceive('driver')->with('linkedin')->andReturnSelf();
    Socialite::shouldReceive('scopes')->with(['openid', 'profile', 'email'])->once()->andReturnSelf();
    Socialite::shouldReceive('redirect')->andReturn($redirectResponse);

    $provider = new LinkedinProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $response = $provider->login($routerData);
    $this->assertEquals($redirectUrl, $response->getTargetUrl());
    $this->assertCount(0, $provider->errors());
});

it('handles the return code and authenticates the user if possible', function () {
    $this->app->config->set('services.linkedin.client_id', 'linkedin_client_id');
    $this->app->config->set('services.linkedin.client_secret', 'linkedin_client_secret');
    $this->app->config->set('auth.providers.users.model', User::class);

    $socialiteId = 'provider-user-id';
    $name = 'Test User';
    $email = 'test@example.com';
    $avatar = 'http://example.com/avatar.jpg';

    Route::get('/', fn () => '')->name('home');
    Route::get('/dashboard', fn () => '')->name('dashboard');
    app('router')->getRoutes()->refreshNameLookups();

    $socialiteUserMock = Mockery::mock(SocialiteUser::class);
    $socialiteUserMock->shouldReceive('getId')->andReturn($socialiteId);
    $socialiteUserMock->shouldReceive('getName')->andReturn($name);
    $socialiteUserMock->shouldReceive('getEmail')->andReturn($email);
    $socialiteUserMock->shouldReceive('getAvatar')->andReturn($avatar);

    Socialite::shouldReceive('driver')->with('linkedin')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUserMock);

    $provider = new LinkedinProvider;
    $routerData = new RouterData('dashboard', 'home', 'home', true);
    $response = $provider->callback($routerData);

    $this->assertTrue(Auth::check());
    $this->assertEquals(1, Auth::user()->id);
    $this->assertCount(0, $provider->errors());
    $this->assertEquals('http://localhost/dashboard', $response->getTargetUrl());
});
