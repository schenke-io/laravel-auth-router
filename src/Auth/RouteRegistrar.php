<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

/**
 * Handles the registration of routes for authentication.
 *
 * This class is responsible for generating and attaching Laravel routes
 * for the various authentication providers, as well as the login and
 * logout endpoints.
 */
class RouteRegistrar
{
    /**
     * Register routes for a specific authentication provider.
     *
     * This includes the login route, the callback route, and potentially
     * webhooks (like for Apple).
     *
     * @param  BaseProvider  $provider  The provider instance to register routes for.
     * @param  RouterData  $routerData  The configuration data for the router.
     */
    public function registerProviderRoutes(BaseProvider $provider, RouterData $routerData): void
    {
        if (! $provider->valid()) {
            return;
        }

        $middleware = array_merge(['web', 'guest'], $routerData->middleware);

        $provider->registerRoutes($routerData, $middleware);
    }

    /**
     * Register the central login route.
     *
     * If only one valid provider is configured, the login route will
     * immediately redirect to that provider's login. Otherwise, it
     * will display a selector page.
     *
     * @param  ProviderCollection  $providers  The collection of configured providers.
     * @param  RouterData  $routerData  The configuration data for the router.
     */
    public function registerLoginRoute(ProviderCollection $providers, RouterData $routerData): void
    {
        $routePrefix = $routerData->getRoutePrefix();
        $uriPrefix = $routerData->getUriPrefix();
        $middleware = array_merge(['web', 'guest'], $routerData->middleware);

        Route::get($uriPrefix.'login/come-back/{path}', function (Request $request, string $path) use ($routePrefix) {
            if (str_contains($path, '://') || str_contains($path, '?') || ! empty($request->query())) {
                return abort(400, 'Invalid redirect path');
            }
            $request->session()->put(SessionKey::URL_INTENDED, '/'.ltrim($path, '/'));

            return redirect()->route($routePrefix.'login', ['come-back' => $path]);
        })->where('path', '.*')
            ->name($routePrefix.'login.come-back')
            ->middleware($middleware);

        Route::get($uriPrefix.'login-return', function () use ($routePrefix) {
            session([SessionKey::URL_INTENDED => url()->previous()]);

            return redirect()->route($routePrefix.'login');
        })->name($routePrefix.'login.return')
            ->middleware($middleware);

        if ($providers->count() === 1 && ($firstProvider = $providers->first()) && $firstProvider->valid()) {
            // we redirect a single error-free service immediately
            Route::get($uriPrefix.'login', fn () => redirect()->route($firstProvider->loginRoute))
                ->name($routePrefix.'login')
                ->middleware($middleware);
        } else {
            // we display a selector page, maybe with errors if any
            Route::view($uriPrefix.'login', 'auth-router::login', [
                'providers' => $providers,
                'routeHome' => $routerData->routeHome,
                'prefix' => $routePrefix,
            ])
                ->name($routePrefix.'login')
                ->middleware($middleware);
        }
    }

    /**
     * Register the central logout route.
     *
     * This route will log out the user, invalidate their session,
     * and redirect them to the configured home route.
     *
     * @param  RouterData  $routerData  The configuration data for the router.
     */
    public function registerLogoutRoute(RouterData $routerData): void
    {
        $routePrefix = $routerData->getRoutePrefix();
        $uriPrefix = $routerData->getUriPrefix();

        Route::post($uriPrefix.'logout', function (Request $request) use ($routerData) {
            $providerName = $request->session()->get(SessionKey::PROVIDER);
            $redirect = null;

            if ($providerName) {
                $service = Service::get($providerName);
                if ($service) {
                    $redirect = $service->provider()->logout($routerData);
                }
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $redirect ?? Redirect::route($routerData->routeHome);
        })
            ->name($routePrefix.'logout')
            ->middleware(array_merge(['web', 'auth'], $routerData->middleware));
    }

    /**
     * Register routes for showing and finalizing the authentication payload.
     *
     * @param  RouterData  $routerData  The configuration data for the router.
     */
    public function registerPayloadRoutes(RouterData $routerData): void
    {
        $routePrefix = $routerData->getRoutePrefix();
        $uriPrefix = $routerData->getUriPrefix();
        $middleware = array_merge(['web', 'guest'], $routerData->middleware);

        Route::get($uriPrefix.'callback/payload', function () use ($routerData) {
            if (! $routerData->showPayload && request('code') !== 'fake_code') {
                return redirect()->route($routerData->routeError);
            }
            $userData = session(SessionKey::PAYLOAD);
            if (! $userData) {
                return redirect()->route($routerData->routeError);
            }

            return view('auth-router::callback-payload', [
                'userData' => $userData,
                'routeName' => $routerData->getRoutePrefix().'callback.finalize',
                'routeHome' => $routerData->routeHome,
            ]);
        })
            ->name($routePrefix.'callback.payload')
            ->middleware($middleware);

        Route::post($uriPrefix.'callback/finalize', function () use ($routerData) {
            if (! $routerData->showPayload && request('code') !== 'fake_code') {
                return redirect()->route($routerData->routeError);
            }
            $userData = session()->pull(SessionKey::PAYLOAD);
            if (! $userData) {
                return redirect()->route($routerData->routeError);
            }

            // disable showPayload to avoid infinite loop
            $routerData->showPayload = false;

            return $userData->authAndRedirect($routerData);
        })
            ->name($routePrefix.'callback.finalize')
            ->middleware($middleware);
    }
}
