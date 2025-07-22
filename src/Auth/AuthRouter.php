<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

class AuthRouter
{
    public function addProvider(BaseProvider $provider, RouterData $routerData): void
    {
        $provider->fillMacro($routerData);
    }

    public function addProviders(ProviderCollection $providers, RouterData $routerData): void
    {
        foreach ($providers as $provider) {
            $this->addProvider($provider, $routerData);
        }
    }

    public function addLogin(ProviderCollection $providers, RouterData $routerData): void
    {
        $firstProvider = $providers->first();

        if ($providers->count() == 1 && $firstProvider->valid()) {
            // we redirect a single error free service immediately
            Route::get('login', fn () => redirect()->route($firstProvider->loginRoute))->name('login');
        } else {
            // we display a selector page, maybe with errors if any
            Route::view('login', 'auth-router::login', [
                'providers' => $providers,
                'routeHome' => $routerData->routeHome,
            ])->name('login');
        }
    }

    /**
     * if not logged in the user gets redirected to the login route from auth-middelware
     * if logged in it gets redirected to $routeHome route
     */
    public function addLogout(string $routeHome): void
    {
        Route::post('logout', function () use ($routeHome) {
            Auth::logout();
            Session::flush();
            Session::regenerate();

            return Redirect::route($routeHome);
        })->name('logout')->middleware('auth');
    }
}
