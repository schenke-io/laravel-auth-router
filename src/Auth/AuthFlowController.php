<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Enums\Service;

class AuthFlowController
{
    public function login(Request $request): mixed
    {
        $routerData = $this->routerData($request);

        $provider = $this->resolveProvider($request->route('provider'), $routerData);
        if (! $provider) {
            return redirect()->route($routerData->routeError);
        }

        return $provider->login($routerData);
    }

    public function callback(Request $request): mixed
    {
        $routerData = $this->routerData($request);

        $provider = $this->resolveProvider($request->route('provider'), $routerData);
        if (! $provider) {
            return redirect()->route($routerData->routeError);
        }

        return $provider->callback($routerData);
    }

    public function backChannelLogout(Request $request): ResponseFactory|Response
    {
        $routerData = $this->routerData($request);

        $provider = $this->resolveProvider($request->route('provider'), $routerData);
        if (! $provider) {
            return response('Unknown provider', 400);
        }

        return $provider->backChannelLogout($request, $routerData);
    }

    public function loginIndex(Request $request): mixed
    {
        $routerData = $this->routerData($request);
        $providers = $request->route('providers');
        /** @var array<int, string> $errors */
        $errors = $request->route('errors') ?? [];

        $collection = ProviderCollection::fromTextArray($providers);

        foreach ($collection as $provider) {
            $provider->prepare($routerData);
        }

        $collection->applyErrors($errors);

        if ($collection->count() === 1 && ($firstProvider = $collection->first()) && $firstProvider->valid() && Route::has($firstProvider->loginRoute)) {
            return redirect()->route($firstProvider->loginRoute);
        }

        return view('auth-router::login', [
            'providers' => $collection,
            'routeHome' => $routerData->routeHome,
            'prefix' => $routerData->getRoutePrefix(),
        ]);
    }

    public function loginComeBack(Request $request): RedirectResponse
    {
        $routerData = $this->routerData($request);
        $path = $request->route('path');

        if (str_contains($path, '://') || str_contains($path, '?') || ! empty($request->query())) {
            return abort(400, 'Invalid redirect path');
        }
        $request->session()->put(SessionKey::URL_INTENDED, '/'.ltrim($path, '/'));

        return redirect()->route($routerData->getRoutePrefix().'login', ['come-back' => $path]);
    }

    public function loginReturn(Request $request): RedirectResponse
    {
        $routerData = $this->routerData($request);

        session([SessionKey::URL_INTENDED => url()->previous()]);

        return redirect()->route($routerData->getRoutePrefix().'login');
    }

    public function logout(Request $request): RedirectResponse
    {
        $routerData = $this->routerData($request);

        $providerName = $request->session()->get(SessionKey::PROVIDER);
        $redirect = null;

        if ($providerName) {
            $provider = $this->resolveProvider($providerName, $routerData);
            if ($provider) {
                $redirect = $provider->logout($routerData);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $redirect ?? Redirect::route($routerData->routeHome);
    }

    public function payload(Request $request): mixed
    {
        $routerData = $this->routerData($request);

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
    }

    public function finalize(Request $request): mixed
    {
        $routerData = $this->routerData($request);

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
    }

    /**
     * Resolve the RouterData carried as a route default for the current request.
     */
    private function routerData(Request $request): RouterData
    {
        /** @var RouterData $routerData */
        $routerData = $request->route('routerData');

        return $routerData;
    }

    /**
     * Resolve the provider for the given name and prepare it for the request.
     */
    private function resolveProvider(?string $providerName, RouterData $routerData): ?BaseProvider
    {
        if ($providerName === null) {
            return null;
        }

        $service = Service::get($providerName);
        if (! $service) {
            return null;
        }

        $provider = $service->provider();
        $provider->prepare($routerData);

        return $provider;
    }
}
