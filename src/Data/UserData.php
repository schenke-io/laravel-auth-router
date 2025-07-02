<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    /**
     * @param  string  $name  full name of the user, maybe local overwritten
     * @param  string  $email  unique email of the user, cross-linked to other logins
     * @param  string  $avatar  image url or empty
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $avatar
    ) {}

    public static function fromUser(SocialiteUser $user): self
    {
        return new self(
            $user->getName() ?? '',
            $user->getEmail() ?? '',
            $user->getAvatar() ?? ''
        );
    }

    /**
     * @param  array<string,string>  $data
     */
    public static function fromAuth0(array $data): self
    {
        return new self(
            $data['name'] ?? '',
            $data['email'] ?? '',
            $data['picture'] ?? ''
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

        /**
         * Get the user model class configured in the application
         *
         * @var class-string<Model> $userModelClass
         */
        $userModelClass = config('auth.providers.users.model');
        /** @var Authenticatable|null $user */
        $user = $userModelClass::where('email', $this->email)->first();
        if ($user) {
            /** @var Model&object{avatar: string } $user */
            $oldAvatar = $user->avatar;
            $newAvatar = $this->avatar;
            if ($oldAvatar != $newAvatar && strlen($newAvatar) > 10) {
                $user->update(['avatar' => $newAvatar]);
            }
        } else {
            if ($routerData->canAddUsers) {
                $user = (new $userModelClass)->updateOrCreate(
                    ['email' => $this->email],
                    ['name' => $this->name, 'avatar' => $this->avatar]
                );
            } else {
                return Error::UnableToAddNewUsers->redirect($routerData);
            }
        }
        $user->save();
        /** @var Authenticatable $user */
        Auth::guard('web')->login($user);

        if (Session::has('url.intended')) {
            return redirect()->intended();
        } else {
            return redirect()->route($routerData->routeSuccess);
        }
    }
}
