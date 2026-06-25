<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

/**
 * Handles the registration of routes for authentication.
 */
class RouteRegistrar
{
    /**
     * Register wildcard routes for all providers in the collection.
     */
    public function registerWildcardRoutes(ProviderCollection $providers, RouterData $routerData): void
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

    public function registerProviderRoutes(BaseProvider $provider, RouterData $routerData): void
    {
        $provider->prepare($routerData);

        $provider->registerRoutes($routerData, $routerData->guestMiddleware());
    }

    /**
     * Register the central login route.
     */
    public function registerLoginRoute(ProviderCollection $providers, RouterData $routerData): void
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
     * Register the central logout route.
     */
    public function registerLogoutRoute(RouterData $routerData): void
    {
        $routePrefix = $routerData->getRoutePrefix();
        $uriPrefix = $routerData->getUriPrefix();

        Route::post($uriPrefix.'logout', [AuthFlowController::class, 'logout'])
            ->name($routePrefix.'logout')
            ->defaults('routerData', $routerData)
            ->middleware($routerData->authMiddleware());
    }

    /**
     * Register routes for showing and finalizing the authentication payload.
     */
    public function registerPayloadRoutes(RouterData $routerData): void
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
     * Register routes for impersonation.
     */
    public function registerImpersonationRoutes(RouterData $routerData): void
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
