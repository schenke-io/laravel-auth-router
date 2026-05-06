<?php

use Laravel\Socialite\Contracts\User as SocialiteUser;
use SchenkeIo\LaravelAuthRouter\Data\UserData;

it('keeps name empty when name is empty in fromUser', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getName')->andReturn('');
    $socialiteUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
    $socialiteUser->shouldReceive('getId')->andReturn('12345');

    $userData = UserData::fromUser($socialiteUser, 'google');

    expect($userData->name)->toBe('');
});

it('keeps name empty when name is empty in fromAuth0', function () {
    $data = [
        'name' => '',
        'email' => 'auth0@example.com',
        'picture' => 'https://example.com/auth0.jpg',
        'sub' => 'auth0|67890',
    ];

    $userData = UserData::fromAuth0($data);

    expect($userData->name)->toBe('');
});

it('keeps name empty when name is empty in fromWorkOs', function () {
    $user = (object) [
        'firstName' => '',
        'lastName' => null,
        'email' => 'workos@example.com',
        'profilePictureUrl' => 'https://example.com/workos.jpg',
        'id' => 'workos_123',
    ];

    $userData = UserData::fromWorkOs($user);

    expect($userData->name)->toBe('');
});

it('keeps name empty when name is empty in Logto claims', function () {
    $claims = (object) [
        'name' => '',
        'username' => null,
        'email' => 'logto@example.com',
        'picture' => 'https://example.com/logto.jpg',
        'sub' => 'logto_123',
    ];

    $userData = new UserData(
        name: $claims->name ?? $claims->username ?? '',
        email: $claims->email ?? '',
        avatar: $claims->picture ?? '',
        provider: 'logto',
        providerId: $claims->sub,
        isExclusive: true
    );

    expect($userData->name)->toBe('');
});

it('keeps name empty in Passkey callback', function () {
    $email = 'passkey@example.com';
    $userData = new UserData(
        name: '',
        email: $email,
        provider: 'passkey'
    );

    expect($userData->name)->toBe('');
});

it('keeps name empty in Whatsapp callback', function () {
    $email = 'whatsapp@example.com';
    $userData = new UserData(
        name: '',
        email: $email,
        provider: 'whatsapp'
    );

    expect($userData->name)->toBe('');
});

it('keeps name as whitespace when name is only whitespace in constructor', function () {
    $userData = new UserData(
        name: '   ',
        email: 'test@example.com',
        providerId: '999'
    );
    expect($userData->name)->toBe('   ');
});

it('does not use providerId if name is provided', function () {
    $userData = new UserData(
        name: 'Real Name',
        email: 'test@example.com',
        providerId: '999'
    );
    expect($userData->name)->toBe('Real Name');
});

it('keeps name empty if both name and providerId are missing', function () {
    $userData = new UserData(
        name: '',
        email: 'test@example.com',
        providerId: null
    );
    expect($userData->name)->toBe('');
});
