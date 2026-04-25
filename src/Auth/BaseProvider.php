<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
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
            $fromMapping = false;
            if (is_string($config)) {
                $config = ['client_id' => $config];
                Config::set($longKey, $config);
                $fromMapping = true;
            }
            if (is_array($config)) {
                foreach ($this->env() as $key => $env) {
                    if (($config[$key] ?? '') == '') {
                        if ($fromMapping && $key != 'client_id') {
                            continue;
                        }
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

        Config::set('services.'.$this->name.'.redirect', $this->getRedirectUrl());

        Route::get($uriPrefix.$this->loginUri, fn (Request $request) => app()->call([$this, 'login'], ['routerData' => $routerData]))
            ->name($this->loginRoute)
            ->defaults('routerData', $routerData)
            ->middleware($middleware);

        Route::post($uriPrefix.$this->loginUri, fn (Request $request) => app()->call([$this, 'login'], ['routerData' => $routerData]))
            ->defaults('routerData', $routerData)
            ->middleware($middleware);

        Route::get($uriPrefix.$this->callbackUri, fn (Request $request) => app()->call([$this, 'callback'], ['routerData' => $routerData]))
            ->name($this->callbackRoute)
            ->defaults('routerData', $routerData)
            ->middleware($middleware);
    }

    public function getRedirectUrl(): string
    {
        if (Route::has($this->callbackRoute)) {
            return route($this->callbackRoute);
        }

        return url($this->callbackUri);
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
     * @param  array<string, mixed>  $context
     */
    public function log(RouterData $routerData, string $message, array $context = []): void
    {
        if ($routerData->logChannel) {
            Log::channel($routerData->logChannel)->info($message, array_merge([
                'provider' => $this->name,
            ], $context));
        }
    }
}
