<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Workbench\App\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->config->set('auth.providers.users.model', User::class);
});

it('links provider_id when isExclusive is true and email is found with null provider_id', function () {
    $email = 'test@example.com';
    $providerId = 'google-123';
    User::factory()->create(['email' => $email, 'provider_id' => null]);

    $userData = new UserData('Test User', $email, '', 'google', $providerId, true);
    $routerData = getRouterData(false);

    $userData->authAndRedirect($routerData);

    $user = User::where('email', $email)->first();
    expect($user->provider_id)->toBe($providerId);
});

it('updates email when isExclusive is true, provider_id is found, and email changed', function () {
    $oldEmail = 'old@example.com';
    $newEmail = 'new@example.com';
    $providerId = 'google-123';
    User::factory()->create(['email' => $oldEmail, 'provider_id' => $providerId]);

    $userData = new UserData('Test User', $newEmail, '', 'google', $providerId, true);
    $routerData = getRouterData(false);

    $userData->authAndRedirect($routerData);

    $user = User::where('provider_id', $providerId)->first();
    expect($user->email)->toBe($newEmail);
});

it('returns LoginEmailError when isExclusive is true, provider_id is found, email changed, but new email exists', function () {
    $oldEmail = 'old@example.com';
    $newEmail = 'new@example.com';
    $providerId = 'google-123';
    User::factory()->create(['email' => $oldEmail, 'provider_id' => $providerId]);
    User::factory()->create(['email' => $newEmail, 'provider_id' => 'other-id']);

    $userData = new UserData('Test User', $newEmail, '', 'google', $providerId, true);
    $routerData = getRouterData(false);

    $response = $userData->authAndRedirect($routerData);
    expect(customErrorType($response))->toBe('LoginEmailError');

    $user = User::where('provider_id', $providerId)->first();
    expect($user->email)->toBe($oldEmail);
});

it('creates user when isExclusive is true, nothing found, and canAddUsers is true', function () {
    $email = 'new@example.com';
    $providerId = 'google-123';

    $userData = new UserData('Test User', $email, '', 'google', $providerId, true);
    $routerData = getRouterData(true);

    $userData->authAndRedirect($routerData);

    expect(User::count())->toBe(1);
    $user = User::first();
    expect($user->email)->toBe($email)->and($user->provider_id)->toBe($providerId);
});

it('returns UnableToAddNewUsers when isExclusive is true, nothing found, and canAddUsers is false', function () {
    $email = 'new@example.com';
    $providerId = 'google-123';

    $userData = new UserData('Test User', $email, '', 'google', $providerId, true);
    $routerData = getRouterData(false);

    $response = $userData->authAndRedirect($routerData);
    expect(customErrorType($response))->toBe('UnableToAddNewUsers');
    expect(User::count())->toBe(0);
});
