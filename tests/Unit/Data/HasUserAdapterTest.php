<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Unit\Data;

pest()->group('unit');

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;
use SchenkeIo\LaravelAuthRouter\Data\HasUserAdapter;

class TestHasUserAdapter
{
    use HasUserAdapter {
        fillModel as public;
    }
}

class MockAuthenticatableUser extends Model implements AuthenticatableRouterUser
{
    public string $userName = '';

    public string $userEmail = '';

    public string $userAvatar = '';

    public string $userProviderId = '';

    public function setName(string $name): void
    {
        $this->userName = $name;
    }

    public function setEmail(string $email): void
    {
        $this->userEmail = $email;
    }

    public function getEmail(): ?string
    {
        return $this->userEmail;
    }

    public function setAvatar(string $avatar): void
    {
        $this->userAvatar = $avatar;
    }

    public function findByEmail(string $email): ?Model
    {
        return null;
    }

    public function findByProviderId(string $providerId): ?Model
    {
        return null;
    }

    public function setProviderId(string $providerId): void
    {
        $this->userProviderId = $providerId;
    }

    public function getProviderId(): ?string
    {
        return $this->userProviderId;
    }
}

class MockSimpleUser extends Model
{
    protected $guarded = [];
}

it('fills AuthenticatableRouterUser correctly with non-empty values', function () {
    $adapter = new TestHasUserAdapter;
    $user = new MockAuthenticatableUser;

    $adapter->fillModel($user, 'Name', 'email@example.com', 'https://avatar.com/img.png', true, 'prov-123');

    expect($user->userName)->toBe('Name')
        ->and($user->userEmail)->toBe('email@example.com')
        ->and($user->userAvatar)->toBe('https://avatar.com/img.png')
        ->and($user->userProviderId)->toBe('prov-123');
});

it('does not overwrite name and email in AuthenticatableRouterUser if they are empty', function () {
    $adapter = new TestHasUserAdapter;
    $user = new MockAuthenticatableUser;
    $user->setName('Old Name');
    $user->setEmail('old@example.com');

    $adapter->fillModel($user, '', '', 'https://avatar.com/img.png');

    expect($user->userName)->toBe('Old Name')
        ->and($user->userEmail)->toBe('old@example.com')
        ->and($user->userAvatar)->toBe('https://avatar.com/img.png');
});

it('fills non-AuthenticatableRouterUser correctly when isNew is true', function () {
    $adapter = new TestHasUserAdapter;
    $user = new MockSimpleUser;

    $adapter->fillModel($user, 'Name', 'email@example.com', 'https://avatar.com/img.png', true, 'prov-123');

    expect($user->getAttribute('name'))->toBe('Name')
        ->and($user->getAttribute('email'))->toBe('email@example.com')
        ->and($user->getAttribute('avatar'))->toBe('https://avatar.com/img.png')
        ->and($user->getAttribute('provider_id'))->toBe('prov-123');
});

it('updates avatar for non-AuthenticatableRouterUser when isNew is false and condition met', function () {
    $adapter = new TestHasUserAdapter;
    $user = new MockSimpleUser;
    $user->setAttribute('avatar', 'https://old.com/avatar.png');

    $newAvatar = 'https://new.com/avatar.png'; // length > 10
    $adapter->fillModel($user, 'Name', 'email@example.com', $newAvatar, false);

    expect($user->getAttribute('avatar'))->toBe($newAvatar);
});

it('does not update avatar for non-AuthenticatableRouterUser when length <= 10', function () {
    $adapter = new TestHasUserAdapter;
    $user = new MockSimpleUser;
    $user->setAttribute('avatar', 'https://old.com/avatar.png');

    $newAvatar = 'too-short'; // length 9
    $adapter->fillModel($user, 'Name', 'email@example.com', $newAvatar, false);

    expect($user->getAttribute('avatar'))->toBe('https://old.com/avatar.png');
});

it('does not update avatar for non-AuthenticatableRouterUser when same as old', function () {
    $adapter = new TestHasUserAdapter;
    $user = new MockSimpleUser;
    $user->setAttribute('avatar', 'https://old.com/avatar.png');

    $newAvatar = 'https://old.com/avatar.png';
    $adapter->fillModel($user, 'Name', 'email@example.com', $newAvatar, false);

    expect($user->getAttribute('avatar'))->toBe($newAvatar);
});

it('does not set provider_id if null in AuthenticatableRouterUser', function () {
    $adapter = new TestHasUserAdapter;
    $user = new MockAuthenticatableUser;
    $user->setProviderId('old-id');

    $adapter->fillModel($user, 'Name', 'email@example.com', 'https://avatar.com/img.png', true, null);

    expect($user->userProviderId)->toBe('old-id');
});

it('does not set provider_id if null in non-AuthenticatableRouterUser', function () {
    $adapter = new TestHasUserAdapter;
    $user = new MockSimpleUser;
    $user->setAttribute('provider_id', 'old-id');

    $adapter->fillModel($user, 'Name', 'email@example.com', 'https://avatar.com/img.png', true, null);

    expect($user->getAttribute('provider_id'))->toBe('old-id');
});

it('keeps name empty for new non-AuthenticatableRouterUser if provided name is empty', function () {
    $adapter = new TestHasUserAdapter;
    $user = new MockSimpleUser;

    $adapter->fillModel($user, '', 'email@example.com', 'https://avatar.com/img.png', true);

    expect($user->getAttribute('name'))->toBe('');
});
