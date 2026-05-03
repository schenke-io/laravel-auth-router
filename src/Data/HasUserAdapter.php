<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;

trait HasUserAdapter
{
    /**
     * @param  class-string<Model>  $userModelClass
     */
    protected function findUserByProviderId(string $userModelClass, string $providerId): ?Model
    {
        if (is_subclass_of($userModelClass, AuthenticatableRouterUser::class)) {
            /** @var AuthenticatableRouterUser $userFactory */
            $userFactory = new $userModelClass;

            return $userFactory->findByProviderId($providerId);
        }

        return $userModelClass::where('provider_id', $providerId)->first();
    }

    /**
     * @param  class-string<Model>  $userModelClass
     */
    protected function findUserByEmail(string $userModelClass, string $email): ?Model
    {
        if (is_subclass_of($userModelClass, AuthenticatableRouterUser::class)) {
            /** @var AuthenticatableRouterUser $userFactory */
            $userFactory = new $userModelClass;

            return $userFactory->findByEmail($email);
        }

        return $userModelClass::where('email', $email)->first();
    }

    protected function getModelProviderId(Model $user): ?string
    {
        if ($user instanceof AuthenticatableRouterUser) {
            return $user->getProviderId();
        }

        return $user->getAttribute('provider_id');
    }

    protected function setModelProviderId(Model $user, string $providerId): void
    {
        if ($user instanceof AuthenticatableRouterUser) {
            $user->setProviderId($providerId);
        } else {
            $user->setAttribute('provider_id', $providerId);
        }
    }

    protected function getModelEmail(Model $user): ?string
    {
        if ($user instanceof AuthenticatableRouterUser) {
            return $user->getEmail();
        }

        return $user->getAttribute('email');
    }

    protected function setModelEmail(Model $user, string $email): void
    {
        if ($user instanceof AuthenticatableRouterUser) {
            $user->setEmail($email);
        } else {
            $user->setAttribute('email', $email);
        }
    }

    protected function fillModel(Model $user, string $name, string $email, string $avatar, bool $isNew = false, ?string $providerId = null): void
    {
        if ($user instanceof AuthenticatableRouterUser) {
            $user->setName($name);
            $user->setEmail($email);
            $user->setAvatar($avatar);
            if ($providerId) {
                $user->setProviderId($providerId);
            }
        } else {
            if ($isNew) {
                $user->setAttribute('name', $name);
                $user->setAttribute('email', $email);
                $user->setAttribute('avatar', $avatar);
                if ($providerId) {
                    $user->setAttribute('provider_id', $providerId);
                }
            } else {
                $oldAvatar = $user->getAttribute('avatar');
                if ($oldAvatar != $avatar && strlen($avatar) > 10) {
                    $user->setAttribute('avatar', $avatar);
                }
            }
        }
    }
}
