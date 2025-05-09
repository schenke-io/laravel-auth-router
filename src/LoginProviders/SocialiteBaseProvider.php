<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Symfony\Component\HttpFoundation\RedirectResponse as SymRedirectResponse;

abstract class SocialiteBaseProvider extends BaseProvider
{
    /**
     * key: expected key in config(system), value: suggested name ov ENV key,
     * used in testing and in documentation
     *
     * @return array<string,string>
     */
    public function env(): array
    {
        $name = strtoupper($this->name);

        return [
            'client_id' => $name.'_CLIENT_ID',
            'client_secret' => $name.'_CLIENT_SECRET',
        ];
    }

    public function login(): SymRedirectResponse|RedirectResponse
    {
        return Socialite::driver($this->name)->redirect();
    }

    /**
     * handles the return code and authenticate the user if possible
     */
    public function callback(RouterData $routerData): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($this->name)->user();

            return UserData::fromUser($socialUser)->authAndRedirect($routerData);
        } catch (\Exception $e) {
            return Error::LocalAuth->redirect($routerData, $e->getMessage());
        }
    }
}
