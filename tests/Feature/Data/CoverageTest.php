<?php

namespace Tests\Feature\Data;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SchenkeIo\LaravelAuthRouter\Data\UserData;

class SimpleUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['auth.providers.users.model' => SimpleUser::class]);
});

it('covers HasUserAdapter fallback methods for non-AuthenticatableRouterUser', function () {
    $email = 'simple@example.com';
    $name = 'Simple User';
    $avatar = 'https://avatar.com/simple.png';
    $providerId = 'simple-123';

    // Cover Line 91: fillModel with $isNew = true and $providerId
    $userData = new UserData($name, $email, $avatar, 'simple', $providerId);
    $routerData = getRouterData(true);
    $userData->authAndRedirect($routerData);

    $user = SimpleUser::where('email', $email)->first();
    expect($user->provider_id)->toBe($providerId);

    // Cover Line 64 (getModelEmail) and 72 (setModelEmail)
    $newEmail = 'updated@example.com';
    // we need isExclusive to trigger the email update logic in line 130
    $userDataUpdate = new UserData($name, $newEmail, $avatar, 'simple', $providerId, true);
    $userDataUpdate->authAndRedirect($routerData);

    $user->refresh();
    expect($user->email)->toBe($newEmail);
});

it('covers UserData line 157: ExclusiveProvider error when providerId is null', function () {
    $email = 'exclusive@example.com';
    SimpleUser::create([
        'email' => $email,
        'name' => 'Existing',
        'provider_id' => 'some-id',
    ]);

    // isExclusive = true, providerId = null
    $userData = new UserData('Name', $email, '', 'google', null, true);
    $routerData = getRouterData(false);

    $response = $userData->authAndRedirect($routerData);

    expect(customErrorType($response))->toBe('ExclusiveProvider');
});
