<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

/**
 * Class AuthRouter
 *
 * Handles the registration of authentication routes and redirects for various providers.
 *
 * Main Responsibilities:
 * - Provider Management: Registers routes for individual or multiple authentication providers.
 * - Login Handling: Sets up the main login route.
 * - Logout Handling: Configures the logout route and its redirection logic.
 * - Payload Management: Registers routes for handling user data payloads.
 * - Impersonation: Sets up routes for user impersonation.
 *
 * Usage Example:
 * ```php
 * $authRouter = new AuthRouter();
 * $authRouter->addProviders($providers, $routerData);
 * $authRouter->addLogin($providers, $routerData);
 * ```
 */
class AuthRouter
{
    /**
     * Register routes for a single authentication provider.
     */
    public function addProvider(BaseProvider $provider, RouterData $routerData): void
    {
        (new RouteRegistrar)->registerProviderRoutes($provider, $routerData);
    }

    /**
     * Register routes for a collection of authentication providers.
     */
    public function addProviders(ProviderCollection $providers, RouterData $routerData): void
    {
        $registrar = new RouteRegistrar;
        $registrar->registerWildcardRoutes($providers, $routerData);
    }

    /**
     * Register the main login route.
     */
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

    /**
     * Register routes for handling user data payloads.
     */
    public function addPayloadRoutes(RouterData $routerData): void
    {
        (new RouteRegistrar)->registerPayloadRoutes($routerData);
    }

    /**
     * Register routes for user impersonation.
     */
    public function addImpersonationRoutes(RouterData $routerData): void
    {
        (new RouteRegistrar)->registerImpersonationRoutes($routerData);
    }
}
