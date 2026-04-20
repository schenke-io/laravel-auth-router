<?php

namespace Workbench\App\Services;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class FakeSocialiteDriver extends AbstractProvider
{
    protected string $driverName = 'fake';

    public function setDriverName(string $driverName): self
    {
        $this->driverName = $driverName;

        return $this;
    }

    protected function getAuthUrl($state): string
    {
        return route('fake-socialite', ['driver' => $this->driverName, 'state' => $state]);
    }

    protected function getTokenUrl(): string
    {
        return '';
    }

    protected function getUserByToken($token): array
    {
        return [
            'id' => 'fake_id',
            'nickname' => 'fake_user',
            'name' => 'Fake User',
            'email' => 'fake-user@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ];
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['nickname'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => $user['avatar'],
        ]);
    }

    public function user()
    {
        if ($this->request->get('code') !== 'fake_code') {
            throw new \Exception('Invalid fake code');
        }

        return $this->mapUserToObject($this->getUserByToken('fake_token'));
    }
}
