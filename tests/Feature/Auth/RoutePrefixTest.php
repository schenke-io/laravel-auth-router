<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config()->set('services.google', [
        'client_id' => 'google-id',
        'client_secret' => 'google-secret',
    ]);
    config()->set('services.facebook', [
        'client_id' => 'facebook-id',
        'client_secret' => 'facebook-secret',
    ]);
    config()->set('services.amazon', [
        'client_id' => 'amazon-id',
        'client_secret' => 'amazon-secret',
    ]);
});

it('registers prefixed routes', function () {
    // google is now valid due to config
    $providers = ['google'];

    Route::authRouter($providers)->success('home')->error('home')->prefix('auth');

    app('router')->getRoutes()->refreshNameLookups();
    $routes = routeNames();

    // Check main login route
    expect($routes)->toContain('auth.login')
        ->and(route('auth.login'))->toContain('/auth/login');

    // Check provider routes
    expect($routes)->toContain('auth.login.google')
        ->and(route('auth.login.google'))->toContain('/auth/login/google');

    expect($routes)->toContain('auth.callback.google')
        ->and(route('auth.callback.google'))->toContain('/auth/callback/google');

    // Check logout route
    expect($routes)->toContain('auth.logout')
        ->and(route('auth.logout'))->toContain('/auth/logout');
});

it('works with nested prefixes', function () {
    Route::authRouter(['facebook'])->success('home')->error('home')->prefix('v1/social');

    app('router')->getRoutes()->refreshNameLookups();
    $routes = routeNames();

    expect($routes)->toContain('v1.social.login')
        ->and(route('v1.social.login'))->toContain('/v1/social/login');

    expect($routes)->toContain('v1.social.login.facebook')
        ->and(route('v1.social.login.facebook'))->toContain('/v1/social/login/facebook');
});

it('does not affect global routes if not prefixed', function () {
    Route::authRouter(['amazon'])->success('home')->error('home');

    $routes = routeNames();

    expect($routes)->toContain('login.amazon');
    expect($routes)->toContain('login');
});
