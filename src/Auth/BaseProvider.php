<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

/**
 * Base class for all login providers, handling configuration and route registration.
 *
 * Each login provider (e.g., Google, WorkOS) extends this class or its derivatives.
 * It manages the registration of login and callback routes, handles configuration
 * checks, and determines the appropriate blade view for the login button.
 */
abstract class BaseProvider
{
    public string $name;

    public string $loginUri;

    public string $loginRoute;

    public string $callbackUri;

    public string $callbackRoute;

    public readonly ?Service $service;

    public string $blade = '';

    /**
     * @var string[]
     */
    protected array $errors = [];

    public function __construct(?string $name = null)
    {
        if ($name) {
            $givenName = $name;
        } else {
            $text = explode('Provider', class_basename($this));
            $givenName = strtolower($text[0]);
        }
        $this->service = Service::get($givenName);
        $this->name = $this->service->name ?? 'unknown';
        $this->loginUri = 'login/'.$this->name;
        $this->loginRoute = 'login.'.$this->name;
        $this->callbackUri = 'callback/'.$this->name;
        $this->callbackRoute = 'callback.'.$this->name;
        if ($this->service) {
            $longKey = 'services.'.$this->name;
            $config = config($longKey);
            if (is_array($config)) {
                foreach ($this->env() as $key => $env) {
                    if (($config[$key] ?? '') == '') {
                        $this->errors[] = Error::ConfigNotSet->trans(['key' => $longKey.'.'.$key, 'env' => $env]);
                    }
                }
            } else {
                $this->errors[] = Error::ServiceNotSet->trans(['name' => $this->name]);
            }
        }
        $this->blade = 'auth-router::provider.'.($this->service && $this->valid() ? $this->name : 'error');
    }

    /**
     * key: expected key in config(system), value: suggested name ov ENV key,
     * used in testing and in documentation
     *
     * @return array<string,string>
     */
    abstract public function env(): array;

    abstract public function isSocial(): bool;

    abstract public function login(RouterData $routerData): mixed;

    abstract public function callback(RouterData $routerData): mixed;

    /**
     * @param  array<int, string>  $middleware
     */
    public function registerRoutes(RouterData $routerData, array $middleware): void
    {
        $uriPrefix = $routerData->getUriPrefix();
        $routePrefix = $routerData->getRoutePrefix();

        $this->loginRoute = $routePrefix.$this->loginRoute;
        $this->callbackRoute = $routePrefix.$this->callbackRoute;

        // make absolute URLs for redirect and callback URIs
        $fullRedirectUri = url($uriPrefix.$this->callbackUri);
        // we must store the config just value just here
        \Illuminate\Support\Facades\Config::set('services.'.$this->name.'.redirect', $fullRedirectUri);

        \Illuminate\Support\Facades\Route::get($uriPrefix.$this->loginUri, fn (\Illuminate\Http\Request $request) => app()->call([$this, 'login'], ['routerData' => $routerData]))
            ->name($this->loginRoute)
            ->defaults('routerData', $routerData)
            ->middleware($middleware);

        \Illuminate\Support\Facades\Route::post($uriPrefix.$this->loginUri, fn (\Illuminate\Http\Request $request) => app()->call([$this, 'login'], ['routerData' => $routerData]))
            ->defaults('routerData', $routerData)
            ->middleware($middleware);

        \Illuminate\Support\Facades\Route::get($uriPrefix.$this->callbackUri, fn (\Illuminate\Http\Request $request) => app()->call([$this, 'callback'], ['routerData' => $routerData]))
            ->name($this->callbackRoute)
            ->defaults('routerData', $routerData)
            ->middleware($middleware);
    }

    /*
     * ========================================================================
     *                  controller methods
     */

    public function getAction(string $method): string
    {
        return static::class.'@'.$method;
    }

    /*
 * ========================================================================
 *                  error methods
 */

    public function addError(string $smg): void
    {
        $this->errors[] = $smg;
        $this->blade = 'auth-router::provider.error';
    }

    /**
     * @return string[]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function valid(): bool
    {
        return count($this->errors) === 0;
    }

    /**
     * Returns the name of the database column used for this provider's unique ID.
     * Default convention: {provider}_id (e.g., "google_id")
     */
    public function getProviderIdField(): ?string
    {
        if (config("services.{$this->name}.user_id_field")) {
            return $this->name.'_id';
        }

        return null;
    }
}
