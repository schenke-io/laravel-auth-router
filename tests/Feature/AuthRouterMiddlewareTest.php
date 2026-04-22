<?php

use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Auth\AuthRouterBuilder;

it('can pass middleware to authRouter macro', function () {
    $this->app->config->set('services.google.client_id', 'id');

    // We expect this to work after modification
    $builder = Route::authRouter(['google'])->middleware('web');

    expect($builder)->toBeInstanceOf(AuthRouterBuilder::class);

    $builder->register();

    $route = Route::getRoutes()->getByName('login.google');
    expect($route->middleware())->toContain('web');
});

it('can pass multiple middleware to authRouter macro', function () {
    $this->app->config->set('services.google.client_id', 'id');

    $builder = Route::authRouter(['google'])->middleware(['web', 'auth']);

    $builder->register();

    $route = Route::getRoutes()->getByName('login.google');
    expect($route->middleware())->toContain('web', 'auth');
});
