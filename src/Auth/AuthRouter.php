<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

/**
 * Handles the registration of authentication routes and redirects.
 */
class AuthRouter
{
    public function addProvider(BaseProvider $provider, RouterData $routerData): void
    {
        (new RouteRegistrar)->registerProviderRoutes($provider, $routerData);
    }

    public function addProviders(ProviderCollection $providers, RouterData $routerData): void
    {
        foreach ($providers as $provider) {
            $this->addProvider($provider, $routerData);
        }
    }

    public function addLogin(ProviderCollection $providers, RouterData $routerData): void
    {
        (new RouteRegistrar)->registerLoginRoute($providers, $routerData);
    }

    /**
     * if not logged in, the user gets redirected to the login route from auth-middelware
     * if logged in, it gets redirected to $routeHome route
     */
    public function addLogout(RouterData $routerData): void
    {
        (new RouteRegistrar)->registerLogoutRoute($routerData);
    }

    public function addPayloadRoutes(RouterData $routerData): void
    {
        (new RouteRegistrar)->registerPayloadRoutes($routerData);
    }
}
