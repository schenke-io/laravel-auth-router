<?php

use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\AuthRouterServiceProvider;

it('registers routes with a custom name instead of prefix', function () {
    (new AuthRouterServiceProvider(app()))->boot();

    Route::authRouter('google')->name('custom')->success('home');

    expect(Route::has('custom.login'))->toBeTrue()
        ->and(Route::has('custom.login.google'))->toBeTrue()
        ->and(Route::has('custom.callback.google'))->toBeTrue()
        ->and(Route::has('custom.logout'))->toBeTrue();
});

it('prefers route name over prefix for route naming but keeps prefix for URI', function () {
    (new AuthRouterServiceProvider(app()))->boot();

    Route::authRouter('google')->prefix('my-prefix')->name('custom')->success('home');

    // Route names should use 'custom'
    expect(Route::has('custom.login'))->toBeTrue()
        ->and(Route::has('custom.login.google'))->toBeTrue();

    // URIs should still use 'my-prefix'
    expect(route('custom.login'))->toContain('/my-prefix/login');
    expect(route('custom.login.google'))->toContain('/my-prefix/login/google');
});
