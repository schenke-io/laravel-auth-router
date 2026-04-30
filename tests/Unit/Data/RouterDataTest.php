<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Unit\Data;

use SchenkeIo\LaravelAuthRouter\Data\RouterData;

it('can be instantiated', function () {
    $data = new RouterData(
        routeSuccess: 'success',
        routeError: 'error',
        routeHome: 'home'
    );

    expect($data->routeSuccess)->toBe('success')
        ->and($data->routeError)->toBe('error')
        ->and($data->routeHome)->toBe('home');
});

it('can be restored via __set_state', function () {
    $properties = [
        'routeSuccess' => 'success',
        'routeError' => 'error',
        'routeHome' => 'home',
        'canAddUsers' => true,
        'rememberMe' => false,
        'prefix' => 'auth',
        'routeName' => 'auth',
        'emailConfirm' => null,
        'middleware' => ['web'],
        'showPayload' => false,
        'logChannel' => 'null',
    ];

    $data = RouterData::__set_state($properties);

    expect($data)->toBeInstanceOf(RouterData::class)
        ->and($data->routeSuccess)->toBe('success')
        ->and($data->routeError)->toBe('error')
        ->and($data->routeHome)->toBe('home')
        ->and($data->canAddUsers)->toBe(true)
        ->and($data->rememberMe)->toBe(false)
        ->and($data->prefix)->toBe('auth')
        ->and($data->routeName)->toBe('auth')
        ->and($data->middleware)->toBe(['web']);
});

it('calculates route prefix correctly', function () {
    $data = new RouterData('s', 'e', 'h', prefix: 'auth');
    expect($data->getRoutePrefix())->toBe('auth.');

    $data = new RouterData('s', 'e', 'h', routeName: 'custom');
    expect($data->getRoutePrefix())->toBe('custom.');

    $data = new RouterData('s', 'e', 'h', prefix: 'a/b', routeName: 'c.d');
    expect($data->getRoutePrefix())->toBe('c.d.');
});

it('calculates uri prefix correctly', function () {
    $data = new RouterData('s', 'e', 'h', prefix: 'auth');
    expect($data->getUriPrefix())->toBe('auth/');

    $data = new RouterData('s', 'e', 'h', prefix: '/auth/');
    expect($data->getUriPrefix())->toBe('auth/');

    $data = new RouterData('s', 'e', 'h');
    expect($data->getUriPrefix())->toBe('');
});
