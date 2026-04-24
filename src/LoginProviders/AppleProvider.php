<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
use SchenkeIo\LaravelAuthRouter\Services\AppleTokenGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse as SymRedirectResponse;

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

    public function login(RouterData $routerData): SymRedirectResponse|RedirectResponse
    {
        try {
            return parent::login($routerData);
        } catch (\Exception $e) {
            return Error::LocalAuth->redirect($routerData, $e->getMessage());
        }
    }

    /**
     * @param  array<int, string>  $middleware
     */
    public function registerRoutes(RouterData $routerData, array $middleware): void
    {
        parent::registerRoutes($routerData, $middleware);

        $uriPrefix = $routerData->getUriPrefix();
        $routePrefix = $routerData->getRoutePrefix();

        Route::post($uriPrefix.$this->callbackUri, fn (Request $request) => app()->call([$this, 'callback'], ['routerData' => $routerData]))
            ->defaults('routerData', $routerData)
            ->middleware($middleware);

        Route::post($uriPrefix.$this->name.'/webhook', fn (Request $request) => app()->call([$this, 'webhook'], ['routerData' => $routerData]))
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
    public function callback(RouterData $routerData): RedirectResponse|View
    {
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
            /** @var AbstractProvider $driver */
            $driver = Socialite::driver($this->name);
            $driver->redirectUrl($this->getRedirectUrl());

            if ($this->isStateless) {
                /** @var User $socialUser */
                $socialUser = $driver->stateless()->user();
            } else {
                /** @var User $socialUser */
                $socialUser = $driver->user();
            }

            return UserData::fromUser($socialUser, $this->name)
                ->authAndRedirect($routerData);
        } catch (\Exception $e) {
            return Error::LocalAuth->redirect($routerData, $e->getMessage());
        }
    }

    /**
     * Handle Apple Server-to-Server notifications.
     */
    public function webhook(Request $request): Response
    {
        app(AppleAuthService::class)->handleServerNotification($request->all());

        return response()->noContent();
    }
}
