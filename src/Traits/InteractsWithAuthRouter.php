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
        $this->avatar = $avatar;
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

    public function findByProvider(string $provider, string $id): ?Model
    {
        /** @var Model $this */
        if (config("services.$provider.user_id_field")) {
            return $this->where($provider.'_id', $id)->first();
        }

        return null;
    }

    public function setProviderId(string $provider, string $id, ?string $fieldName = null): void
    {
        /** @var Model $this */
        $field = $fieldName ?: $provider.'_id';
        if (config("services.$provider.user_id_field")) {
            $this->{$field} = ($id === '') ? null : $id;
        }
    }
}
