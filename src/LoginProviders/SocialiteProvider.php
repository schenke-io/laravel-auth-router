<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Symfony\Component\HttpFoundation\RedirectResponse as SymRedirectResponse;

/**
 * Base class for all login providers that utilize Laravel Socialite.
 */
abstract class SocialiteProvider extends BaseProvider
{
    public readonly bool $isStateless;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->isStateless = (bool) Config::get('services.'.strtolower($this->name).'.stateless', false);
    }

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

    public function isSocial(): bool
    {
        return true;
    }

    public function login(RouterData $routerData): SymRedirectResponse|RedirectResponse
    {
        $this->beforeRequest();
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($this->getSocialiteDriverName());

        $scopes = $this->getScopes();
        if (count($scopes) > 0) {
            $driver->scopes($scopes);
        }

        if ($this->isStateless) {
            return $driver->stateless()->redirect();
        } else {
            return $driver->redirect();
        }
    }

    /**
     * handles the return code and authenticate the user if possible
     */
    public function callback(RouterData $routerData): RedirectResponse|View
    {
        if (request('denied') || request('error')) {
            return Error::LocalAuth->redirect($routerData, 'User cancelled authentication');
        }

        if (request('code') === 'fake_code') {
            $userData = new UserData(
                name: 'Fake User',
                email: 'fake@example.com',
                avatar: 'https://via.placeholder.com/150',
                provider: $this->name
            );

            return view('auth-router::callback-payload', [
                'userData' => $userData,
                'routeName' => $routerData->getRoutePrefix().'callback.finalize',
                'routeHome' => $routerData->routeHome,
            ]);
        }

        try {
            $this->beforeRequest();
            /** @var AbstractProvider|null $driver */
            $driver = Socialite::driver($this->getSocialiteDriverName());

            if (! $driver) {
                return Error::LocalAuth->redirect($routerData, "Socialite driver [{$this->getSocialiteDriverName()}] not found");
            }

            if ($this->isStateless) {
                $socialUser = $driver->stateless()->user();
            } else {
                $socialUser = $driver->user();
            }

            return UserData::fromUser($socialUser, $this->name)->authAndRedirect($routerData);
        } catch (\Exception $e) {
            return Error::LocalAuth->redirect($routerData, $e->getMessage());
        }
    }

    /**
     * Hook to allow dynamic configuration before interacting with Socialite.
     */
    protected function beforeRequest(): void {}

    /**
     * @return string[]
     */
    protected function getScopes(): array
    {
        return [];
    }

    protected function getSocialiteDriverName(): string
    {
        return $this->name;
    }
}
