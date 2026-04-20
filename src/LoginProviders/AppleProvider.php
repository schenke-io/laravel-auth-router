<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
use SchenkeIo\LaravelAuthRouter\Services\AppleTokenGenerator;

/**
 * Social login with Apple.
 *
 * This provider uses dynamic client secret generation via AppleTokenGenerator.
 * It also handles Server-to-Server notifications.
 *
 * @link https://developer.apple.com/sign-in-with-apple/
 */
class AppleProvider extends SocialiteProvider
{
    /**
     * @return array<string,string>
     */
    public function env(): array
    {
        $name = strtoupper($this->name);

        return [
            'client_id' => $name.'_CLIENT_ID',
            'team_id' => $name.'_TEAM_ID',
            'key_id' => $name.'_KEY_ID',
            'private_key' => $name.'_PRIVATE_KEY',
        ];
    }

    public function isSocial(): bool
    {
        return true;
    }

    /**
     * @param  array<int, string>  $middleware
     */
    public function registerRoutes(RouterData $routerData, array $middleware): void
    {
        parent::registerRoutes($routerData, $middleware);

        $uriPrefix = $routerData->getUriPrefix();
        $routePrefix = $routerData->getRoutePrefix();

        \Illuminate\Support\Facades\Route::post($uriPrefix.$this->callbackUri, fn (\Illuminate\Http\Request $request) => app()->call([$this, 'callback'], ['routerData' => $routerData]))
            ->defaults('routerData', $routerData)
            ->middleware($middleware);

        \Illuminate\Support\Facades\Route::post($uriPrefix.$this->name.'/webhook', fn (\Illuminate\Http\Request $request) => app()->call([$this, 'webhook'], ['routerData' => $routerData]))
            ->name($routePrefix.$this->name.'.webhook')
            ->middleware($routerData->middleware);
    }

    /**
     * Generate and inject the dynamic client secret for Apple before Socialite request.
     */
    protected function beforeRequest(): void
    {
        $config = config("services.{$this->name}");

        $clientSecret = app(AppleTokenGenerator::class)->generate(
            (string) ($config['team_id'] ?? ''),
            (string) ($config['key_id'] ?? ''),
            (string) ($config['private_key'] ?? ''),
            (string) ($config['client_id'] ?? '')
        );

        config(["services.{$this->name}.client_secret" => $clientSecret]);
    }

    /**
     * handles the return code and authenticate the user if possible
     */
    public function callback(\SchenkeIo\LaravelAuthRouter\Data\RouterData $routerData): \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
    {
        if (request('code') === 'fake_code') {
            $userData = new \SchenkeIo\LaravelAuthRouter\Data\UserData(
                name: 'Fake User',
                email: 'fake@example.com',
                avatar: 'https://via.placeholder.com/150',
                provider: $this->name,
                providerId: 'fake-id',
                providerIdField: $this->getProviderIdField()
            );

            return view('auth-router::callback-payload', [
                'userData' => $userData,
                'routeName' => $routerData->getRoutePrefix().'callback.finalize',
                'routeHome' => $routerData->routeHome,
            ]);
        }

        try {
            $this->beforeRequest();
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = \Laravel\Socialite\Facades\Socialite::driver($this->name);

            if ($this->isStateless) {
                /** @var \Laravel\Socialite\Contracts\User $socialUser */
                $socialUser = $driver->stateless()->user();
            } else {
                /** @var \Laravel\Socialite\Contracts\User $socialUser */
                $socialUser = $driver->user();
            }

            return \SchenkeIo\LaravelAuthRouter\Data\UserData::fromUser($socialUser, $this->name, $this->getProviderIdField())
                ->authAndRedirect($routerData);
        } catch (\Exception $e) {
            return \SchenkeIo\LaravelAuthRouter\Auth\Error::LocalAuth->redirect($routerData, $e->getMessage());
        }
    }

    /**
     * Handle Apple Server-to-Server notifications.
     */
    public function webhook(\Illuminate\Http\Request $request): \Illuminate\Http\Response
    {
        app(AppleAuthService::class)->handleServerNotification($request->all());

        return response()->noContent();
    }
}
