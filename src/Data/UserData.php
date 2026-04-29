<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;
use Spatie\LaravelData\Data;

/**
 * Data object for user information, mapping different provider user formats.
 *
 * This class serves as a central hub for user data collected from various
 * authentication providers (Socialite, Auth0). It provides methods
 * to create a UserData instance from these sources and includes the logic
 * to authenticate the user within the Laravel application, including
 * automatic user creation if configured.
 *
 * Support for the AuthenticatableRouterUser interface allows for more
 * complex user management scenarios by delegating data handling to the
 * application's User model.
 */
class UserData extends Data
{
    /**
     * @param  string  $name  full name of the user, maybe local overwritten
     * @param  string  $email  unique email of the user, cross-linked to other logins
     * @param  string|null  $avatar  image url or empty
     * @param  string  $provider  the login provider
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $avatar = null,
        public string $provider = ''
    ) {}

    public static function fromUser(SocialiteUser $user, string $provider): self
    {
        return new self(
            name: $user->getName() ?? '',
            email: $user->getEmail() ?? '',
            avatar: $user->getAvatar() ?? '',
            provider: $provider
        );
    }

    /**
     * @param  array<string,string>  $data
     */
    public static function fromAuth0(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            email: $data['email'] ?? '',
            avatar: $data['picture'] ?? '',
            provider: 'auth0'
        );
    }

    public static function fromWorkOs(object $user): self
    {
        return new self(
            name: ($user->firstName ?? '').' '.($user->lastName ?? ''),
            email: $user->email ?? '',
            avatar: $user->profilePictureUrl ?? '',
            provider: 'workos'
        );
    }

    public function authAndRedirect(RouterData $routerData): RedirectResponse
    {
        // without any email we redirect
        if (! str_contains($this->email, '@')) {
            return Error::EmailMissing->redirect($routerData);
        }
        // without a valid email we redirect
        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return Error::InvalidEmail->redirect($routerData);
        }

        if ($routerData->showPayload) {
            Session::put('auth-router-payload', $this);

            return redirect()->route($routerData->getRoutePrefix().'callback.payload');
        }

        /**
         * Get the user model class configured in the application
         *
         * @var class-string<Model> $userModelClass
         */
        $userModelClass = config('auth.providers.users.model');

        /** @var Authenticatable|null $user */
        $user = null;

        if (is_subclass_of($userModelClass, AuthenticatableRouterUser::class)) {
            /** @var Model&AuthenticatableRouterUser $userFactory */
            $userFactory = new $userModelClass;
            $user = $userFactory->findByEmail($this->email);
        } else {
            $user = $userModelClass::where('email', $this->email)->first();
        }

        if ($user) {
            if ($user instanceof AuthenticatableRouterUser) {
                $user->setName($this->name);
                $user->setEmail($this->email);
                if ($this->avatar) {
                    $user->setAvatar($this->avatar);
                }
            } else {
                /** @phpstan-ignore-next-line */
                $oldAvatar = $user->avatar;
                $newAvatar = $this->avatar ?? '';
                if ($oldAvatar != $newAvatar && strlen($newAvatar) > 10) {
                    /** @phpstan-ignore-next-line */
                    $user->avatar = $newAvatar;
                }
            }
        } else {
            if ($routerData->canAddUsers) {
                $user = new $userModelClass;
                if ($user instanceof AuthenticatableRouterUser) {
                    $user->setName($this->name);
                    $user->setEmail($this->email);
                    if ($this->avatar) {
                        $user->setAvatar($this->avatar);
                    }
                } else {
                    /** @phpstan-ignore-next-line */
                    $user->email = $this->email;
                    /** @phpstan-ignore-next-line */
                    $user->name = $this->name;
                    if ($this->avatar) {
                        /** @phpstan-ignore-next-line */
                        $user->avatar = $this->avatar;
                    }
                }
            } else {
                return Error::UnableToAddNewUsers->redirect($routerData);
            }
        }
        $user->save();

        /** @var Authenticatable $user */
        Auth::guard('web')->login($user, $routerData->rememberMe);

        if ($routerData->logChannel) {
            Log::channel($routerData->logChannel)->info('AuthRouter success', [
                'provider' => $this->provider,
                'email' => $this->email,
            ]);
        }

        Session::put('auth-router-provider', $this->provider);

        if (Session::has('url.intended')) {
            return redirect()->intended();
        } else {
            return redirect()->route($routerData->routeSuccess);
        }
    }
}
