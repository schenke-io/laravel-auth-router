<?php

namespace SchenkeIo\LaravelAuthRouter\Traits;

use Illuminate\Database\Eloquent\Model;

trait InteractsWithAuthRouter
{
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setAvatar(string $avatar): void
    {
        if (str_starts_with($avatar, 'https://')) {
            $this->avatar = $avatar;
        } else {
            $this->avatar = null;
        }
    }

    public function setEmailVerifiedAt($date): void
    {
        $this->email_verified_at = $date;
    }

    public function findByEmail(string $email): ?Model
    {
        /** @var Model $this */
        return $this->where('email', $email)->first();
    }
}
