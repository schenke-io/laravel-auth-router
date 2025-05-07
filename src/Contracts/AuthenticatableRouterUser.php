<?php

namespace SchenkeIo\LaravelAuthRouter\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * this interface needs to be implemented by the User model in the main app
 */
interface AuthenticatableRouterUser
{
    public function setName(string $name): void;

    public function setEmail(string $email): void;

    public function setAvatar(string $avatar): void;

    public function findByEmail(string $email): Model;
}
