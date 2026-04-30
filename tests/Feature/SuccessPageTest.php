<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\GoogleProvider;
use Workbench\App\Models\User;

it('redirects to the success page after successful login', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');
    $this->app->config->set('auth.providers.users.model', User::class);

    $socialiteId = 'provider-user-id';
    $name = 'Test User';
    $email = 'test@example.com';
    $avatar = 'https://example.com/avatar.jpg';

    // Mock Socialite User
    $socialiteUserMock = Mockery::mock(SocialiteUser::class);
    $socialiteUserMock->shouldReceive('getId')->andReturn($socialiteId);
    $socialiteUserMock->shouldReceive('getName')->andReturn($name);
    $socialiteUserMock->shouldReceive('getEmail')->andReturn($email);
    $socialiteUserMock->shouldReceive('getAvatar')->andReturn($avatar);

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('redirectUrl')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUserMock);

    // Register routes
    // Route::get('/', fn () => 'home')->name('home');
    // Route::get('/success', fn () => 'success')->name('success');
    app('router')->getRoutes()->refreshNameLookups();

    $provider = new GoogleProvider;
    $routerData = new RouterData('success', 'home', 'home', true);

    $response = $provider->callback($routerData);

    $this->assertTrue(Auth::check());
    $this->assertEquals('http://localhost/success', $response->getTargetUrl());

    // Now verify the success page itself (in workbench context)
    $this->get('/success')
        ->assertStatus(200);

    // Check if it's the correct view by checking content
    $this->get('/success')->assertSee('Login Successful');
});
