<?php

pest()->group('feature');

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Workbench\App\Models\User;

uses(LazilyRefreshDatabase::class);

it('can create a user with an empty name', function () {
    $this->app->config->set('auth.providers.users.model', User::class);

    $email = 'nameless@example.com';
    $userData = new UserData(
        name: '',
        email: $email,
        avatar: 'https://example.com/avatar.jpg',
        provider: 'google',
        providerId: 'google-123'
    );

    $routerData = getRouterData(canAddNewUser: true);

    expect(User::where('email', $email)->count())->toBe(0);

    $response = $userData->authAndRedirect($routerData);

    // Assert redirect to success
    expect($response->getTargetUrl())->toBe('http://localhost/route-success');

    // Assert user created in database
    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('');

    // Assert user is authenticated
    expect(Auth::check())->toBeTrue()
        ->and(Auth::user()->id)->toBe($user->id);
});
