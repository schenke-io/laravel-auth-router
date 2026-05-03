<?php

namespace SchenkeIo\LaravelAuthRouter\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * This interface needs to be implemented by the User model in the main app to allow for automated user management.
 */
interface AuthenticatableRouterUser
{
    public function setName(string $name): void;

    public function setEmail(string $email): void;

    public function getEmail(): ?string;

    public function setAvatar(string $avatar): void;

    public function findByEmail(string $email): ?Model;

    public function findByProviderId(string $providerId): ?Model;

    public function setProviderId(string $providerId): void;

    public function getProviderId(): ?string;
}
