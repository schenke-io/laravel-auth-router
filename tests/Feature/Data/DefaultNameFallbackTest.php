<?php

pest()->group('feature');

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Workbench\App\Models\User;

uses(LazilyRefreshDatabase::class);

it('applies email-local fallback for new users', function () {
    $this->app->config->set('auth.providers.users.model', User::class);

    $email = 'john.doe@example.com';
    $userData = new UserData(
        name: '',
        email: $email,
        provider: 'google'
    );

    $routerData = getRouterData(canAddNewUser: true);
    $routerData->defaultName = 'email-local';

    $userData->authAndRedirect($routerData);

    $user = User::where('email', $email)->first();
    expect($user->name)->toBe('john.doe');
});

it('applies Closure fallback for new users', function () {
    $this->app->config->set('auth.providers.users.model', User::class);

    $email = 'closure@example.com';
    $userData = new UserData(
        name: '',
        email: $email,
        provider: 'google'
    );

    $routerData = getRouterData(canAddNewUser: true);
    $routerData->defaultName = fn (UserData $data) => 'User '.$data->email;

    $userData->authAndRedirect($routerData);

    $user = User::where('email', $email)->first();
    expect($user->name)->toBe('User closure@example.com');
});

it('does not apply fallback if name is provided', function () {
    $this->app->config->set('auth.providers.users.model', User::class);

    $email = 'named@example.com';
    $userData = new UserData(
        name: 'Existing Name',
        email: $email,
        provider: 'google'
    );

    $routerData = getRouterData(canAddNewUser: true);
    $routerData->defaultName = 'email-local';

    $userData->authAndRedirect($routerData);

    $user = User::where('email', $email)->first();
    expect($user->name)->toBe('Existing Name');
});

it('does not apply fallback for existing users with empty name', function () {
    $this->app->config->set('auth.providers.users.model', User::class);

    $email = 'existing-empty@example.com';
    $user = User::create([
        'name' => '',
        'email' => $email,
    ]);

    $userData = new UserData(
        name: '',
        email: $email,
        provider: 'google'
    );

    $routerData = getRouterData(canAddNewUser: true);
    $routerData->defaultName = 'email-local';

    $userData->authAndRedirect($routerData);

    $user->refresh();
    expect($user->name)->toBe('');
});
