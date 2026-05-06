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
use SchenkeIo\LaravelAuthRouter\Auth\SessionKey;
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
    use HasUserAdapter;

    /**
     * @param  string  $name  full name of the user, maybe local overwritten
     * @param  string  $email  unique email of the user, cross-linked to other logins
     * @param  string  $avatar  image url or empty
     * @param  string  $provider  the login provider
     * @param  string|null  $providerId  the unique id from the provider
     * @param  bool  $isExclusive  if true, only this provider can be used for this user
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $avatar = '',
        public string $provider = '',
        public ?string $providerId = null,
        public bool $isExclusive = false
    ) {
        if ($this->avatar && ! str_starts_with($this->avatar, 'https://')) {
            /*
             * we truncate potential large data-urls first
             * and then do not use it
             */
            $this->avatar = substr($this->avatar, 0, 10);
            $this->avatar = '';
        }
    }

    public static function fromUser(SocialiteUser $user, string $provider): self
    {
        return new self(
            name: $user->getName() ?? '',
            email: $user->getEmail() ?? '',
            avatar: $user->getAvatar() ?? '',
            provider: $provider,
            providerId: $user->getId()
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
            provider: 'auth0',
            providerId: $data['sub'] ?? null
        );
    }

    public static function fromWorkOs(object $user): self
    {
        return new self(
            name: trim(($user->firstName ?? '').' '.($user->lastName ?? '')),
            email: $user->email ?? '',
            avatar: $user->profilePictureUrl ?? '',
            provider: 'workos',
            providerId: $user->id ?? null
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
            Session::put(SessionKey::PAYLOAD, $this);

            return redirect()->route($routerData->getRoutePrefix().'callback.payload');
        }

        /**
         * Get the user model class configured in the application
         *
         * @var class-string<Model> $userModelClass
         */
        $userModelClass = config('auth.providers.users.model');

        /** @var Model|null $user */
        $user = null;

        if ($this->isExclusive && $this->providerId) {
            $user = $this->findUserByProviderId($userModelClass, $this->providerId);
            if ($user) {
                $storedEmail = $this->getModelEmail($user);
                if ($storedEmail !== $this->email) {
                    if ($this->findUserByEmail($userModelClass, $this->email)) {
                        return Error::LoginEmailError->redirect($routerData);
                    }
                    $this->setModelEmail($user, $this->email);
                }
            } else {
                $user = $this->findUserByEmail($userModelClass, $this->email);
                if ($user) {
                    $storedProviderId = $this->getModelProviderId($user);
                    if ($storedProviderId === null) {
                        $this->setModelProviderId($user, $this->providerId);
                    } elseif ($storedProviderId !== $this->providerId) {
                        return Error::ExclusiveProvider->redirect($routerData, '', ['name' => $this->provider]);
                    }
                }
            }
        } elseif ($routerData->useProviderId && $this->providerId) {
            $user = $this->findUserByProviderId($userModelClass, $this->providerId);
            if (! $user) {
                $user = $this->findUserByEmail($userModelClass, $this->email);
                if ($user) {
                    if ($this->getModelProviderId($user) && $this->getModelProviderId($user) !== $this->providerId) {
                        return Error::MixedProviders->redirect($routerData);
                    }
                    $this->setModelProviderId($user, $this->providerId);
                }
            }
        } else {
            $user = $this->findUserByEmail($userModelClass, $this->email);
            if ($user && $this->isExclusive && $this->getModelProviderId($user) && $this->getModelProviderId($user) !== $this->providerId) {
                return Error::ExclusiveProvider->redirect($routerData, '', ['name' => $this->provider]);
            }
        }

        if ($user) {
            $this->fillModel($user, $this->name, $this->email, $this->avatar, false);
        } else {
            if ($routerData->canAddUsers) {
                $user = new $userModelClass;
                $this->fillModel($user, $this->name, $this->email, $this->avatar, true, $this->providerId);
            } else {
                return Error::UnableToAddNewUsers->redirect($routerData);
            }
        }
        try {
            $user->save();
        } catch (\Throwable $e) {
            return Error::LocalAuth->redirect($routerData, $e->getMessage());
        }

        /** @var Authenticatable $user */
        Auth::guard('web')->login($user, $routerData->rememberMe);

        if ($routerData->logChannel) {
            Log::channel($routerData->logChannel)->info('AuthRouter success', [
                'provider' => $this->provider,
                'email' => $this->email,
            ]);
        }

        Session::put(SessionKey::PROVIDER, $this->provider);

        return redirect()->intended(route($routerData->routeSuccess));
    }
}
