<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\Auth;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Auth\AuthRouterBuilder;
use SchenkeIo\LaravelAuthRouter\Contracts\EmailConfirmInterface;

it('can set all fluent methods', function () {
    $emailConfirm = \Mockery::mock(EmailConfirmInterface::class);

    $builder = new AuthRouterBuilder(['google']);
    $builder->success('success_route')
        ->error('error_route')
        ->home('home_route')
        ->canAddUsers(false)
        ->rememberMe(true)
        ->prefix('auth')
        ->emailConfirm($emailConfirm)
        ->middleware(['web', 'auth']);

    // We can use reflection to check the protected properties if we really want,
    // but the real test is whether it registers the routes correctly on destruction.

    // To trigger destruction, we can unset the builder.
    unset($builder);

    // Now check if routes are registered with the given parameters
    $routes = Route::getRoutes();
    expect($routes->hasNamedRoute('login'))->toBeTrue();
});

it('can log debug information', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('test-channel')
        ->andReturnSelf();

    Log::shouldReceive('debug')
        ->once()
        ->with('AuthRouter registration', \Mockery::type('array'));

    $builder = new AuthRouterBuilder(['google']);
    $builder->debug('test-channel')->register();
});

it('can register routes explicitly', function () {
    $builder = new AuthRouterBuilder(['google']);
    $builder->register();
    $builder->register(); // Test the isRegistered check

    $routes = Route::getRoutes();
    expect($routes->hasNamedRoute('login'))->toBeTrue();
});
