<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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

        // Get the user model class configured in the application
        $userModelClass = config('auth.providers.users.model');
        /** @var Authenticatable|null $user */
        $user = $userModelClass::where('email', $this->email)->first();
        if ($user) {
            $this->partlyUpdate($user);
        } else {
            if ($routerData->canAddUsers) {
                /** @var Authenticatable $user */
                $user = new $userModelClass;
                $this->fullUpdate($user);
            } else {
                return Error::UnableToAddNewUsers->redirect($routerData);
            }
        }
        $user->save();
        Auth::guard('web')->login($user);

        return redirect()->route($routerData->routeSuccess);
    }

    private function fullUpdate(Authenticatable|Model $user): void
    {
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
        ]);
    }

    /**
     * we update only the avatar if it's new and valid
     * if the user change login the old avatar could stay
     */
    private function partlyUpdate(Authenticatable|Model $user): void
    {
        /** @var Model&object{avatar: string } $user */
        $oldAvatar = $user->avatar;
        $newAvatar = $this->avatar;
        if ($oldAvatar != $newAvatar && strlen($newAvatar) > 10) {
            $user->update(['avatar' => $newAvatar]);
        }
    }
}
