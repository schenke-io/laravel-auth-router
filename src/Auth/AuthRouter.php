<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

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
        $provider->prepare($routerData);

        $provider->registerRoutes($routerData, $routerData->guestMiddleware());
    }

    /**
     * Register routes for a collection of authentication providers.
     */
    public function addProviders(ProviderCollection $providers, RouterData $routerData): void
    {
        $uriPrefix = $routerData->getUriPrefix();
        $routePrefix = $routerData->getRoutePrefix();
        $middleware = $routerData->guestMiddleware();
        $providerNames = $providers->names();

        if (empty($providerNames)) {
            return;
        }

        foreach ($providers as $provider) {
            $provider->prepare($routerData);
        }

        Route::match(['get', 'post'], $uriPrefix.'login/{provider}', [AuthFlowController::class, 'login'])
            ->whereIn('provider', $providerNames)
            ->name($routePrefix.'login.provider')
            ->defaults('routerData', $routerData)
            ->middleware($middleware);

        Route::get($uriPrefix.'callback/{provider}', [AuthFlowController::class, 'callback'])
            ->whereIn('provider', $providerNames)
            ->name($routePrefix.'callback.provider')
            ->defaults('routerData', $routerData)
            ->middleware($middleware);

        Route::post($uriPrefix.'logout/{provider}/back-channel', [AuthFlowController::class, 'backChannelLogout'])
            ->whereIn('provider', $providerNames)
            ->name($routePrefix.'logout.provider.back-channel')
            ->defaults('routerData', $routerData)
            ->middleware(array_merge(['web'], $routerData->middleware));
    }

    /**
     * Register the main login route.
     */
    public function addLogin(ProviderCollection $providers, RouterData $routerData): void
    {
        $routePrefix = $routerData->getRoutePrefix();
        $uriPrefix = $routerData->getUriPrefix();
        $middleware = $routerData->guestMiddleware();

        Route::get($uriPrefix.'login/come-back/{path}', [AuthFlowController::class, 'loginComeBack'])
            ->where('path', '.*')
            ->name($routePrefix.'login.come-back')
            ->defaults('routerData', $routerData)
            ->middleware($middleware);

        Route::get($uriPrefix.'login-return', [AuthFlowController::class, 'loginReturn'])
            ->name($routePrefix.'login.return')
            ->defaults('routerData', $routerData)
            ->middleware($middleware);

        foreach ($providers as $provider) {
            if (! $provider->valid()) {
                continue;
            }
            $provider->prepare($routerData);
            $provider->registerRoutes($routerData, $middleware);
        }

        Route::get($uriPrefix.'login', [AuthFlowController::class, 'loginIndex'])
            ->name($routePrefix.'login')
            ->defaults('routerData', $routerData)
            ->defaults('providers', $providers->names())
            ->defaults('errors', $routerData->errors)
            ->middleware($middleware);
    }

    /**
     * if not logged in, the user gets redirected to the login route from auth-middelware
     * if logged in, it gets redirected to $routeHome route
     */
    public function addLogout(RouterData $routerData): void
    {
        $routePrefix = $routerData->getRoutePrefix();
        $uriPrefix = $routerData->getUriPrefix();

        Route::post($uriPrefix.'logout', [AuthFlowController::class, 'logout'])
            ->name($routePrefix.'logout')
            ->defaults('routerData', $routerData)
            ->middleware($routerData->authMiddleware());
    }

    /**
     * Register routes for handling user data payloads.
     */
    public function addPayloadRoutes(RouterData $routerData): void
    {
        $routePrefix = $routerData->getRoutePrefix();
        $uriPrefix = $routerData->getUriPrefix();
        $middleware = $routerData->guestMiddleware();

        Route::get($uriPrefix.'callback/payload', [AuthFlowController::class, 'payload'])
            ->name($routePrefix.'callback.payload')
            ->defaults('routerData', $routerData)
            ->middleware($middleware);

        Route::post($uriPrefix.'callback/finalize', [AuthFlowController::class, 'finalize'])
            ->name($routePrefix.'callback.finalize')
            ->defaults('routerData', $routerData)
            ->middleware($middleware);
    }

    /**
     * Register routes for user impersonation.
     */
    public function addImpersonationRoutes(RouterData $routerData): void
    {
        if ($routerData->impersonateGate === null) {
            return;
        }

        $routePrefix = $routerData->getRoutePrefix();
        $uriPrefix = $routerData->getUriPrefix();
        $middleware = $routerData->authMiddleware();

        Route::get($uriPrefix.'impersonate/start/{user}', [ImpersonationController::class, 'start'])
            ->name($routePrefix.'impersonate.start')
            ->defaults('routerData', $routerData)
            ->middleware(array_merge($middleware, ["can:{$routerData->impersonateGate}"]));

        Route::post($uriPrefix.'impersonate/stop', [ImpersonationController::class, 'stop'])
            ->name($routePrefix.'impersonate.stop')
            ->defaults('routerData', $routerData)
            ->middleware($middleware);
    }
}
