<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Enums\Error;
use SchenkeIo\LaravelAuthRouter\Enums\Service;

/**
 * Base class for all login providers, handling configuration and route registration.
 *
 * Each login provider (e.g., Google, WorkOS) extends this class or its derivatives.
 * It manages the registration of login and callback routes, handles configuration
 * checks, and determines the appropriate blade view for the login button.
 *
 * @method \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response backChannelLogout(\Illuminate\Http\Request $request, \SchenkeIo\LaravelAuthRouter\Data\RouterData $routerData)
 */
abstract class BaseProvider
{
    public string $name;

    public string $loginUri;

    public string $loginRoute;

    public string $callbackUri;

    public string $callbackRoute;

    public string $backChannelLogoutUri;

    public string $backChannelLogoutRoute;

    public ?Service $service;

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
        $this->callbackUri = 'callback/'.$this->name;
        $this->backChannelLogoutUri = 'logout/'.$this->name.'/back-channel';
        $this->loginRoute = 'login.'.$this->name;
        $this->callbackRoute = 'callback.'.$this->name;
        $this->backChannelLogoutRoute = 'logout.'.$this->name.'.back-channel';
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
     * @param  array<string, mixed>  $properties
     */
    public static function __set_state(array $properties): static
    {
        /** @phpstan-ignore new.static */
        return new static($properties['name'] ?? null);
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

    public function logout(RouterData $routerData): ?RedirectResponse
    {
        return null;
    }

    public function getClientId(): ?string
    {
        $config = config('services.'.$this->name);

        return $config['client_id'] ?? $config['app_id'] ?? null;
    }

    public function getIssuer(): ?string
    {
        return null;
    }

    /**
     * @param  string[]  $middleware
     */
    public function registerRoutes(RouterData $routerData, array $middleware): void
    {
        $uriPrefix = $routerData->getUriPrefix();

        Route::match(['get', 'post'], $uriPrefix.$this->loginUri, [AuthFlowController::class, 'login'])
            ->name($this->loginRoute)
            ->defaults('routerData', $routerData)
            ->defaults('provider', $this->name)
            ->middleware($middleware);

        Route::get($uriPrefix.$this->callbackUri, [AuthFlowController::class, 'callback'])
            ->name($this->callbackRoute)
            ->defaults('routerData', $routerData)
            ->defaults('provider', $this->name)
            ->middleware($middleware);

        Route::post($uriPrefix.$this->backChannelLogoutUri, [AuthFlowController::class, 'backChannelLogout'])
            ->name($this->backChannelLogoutRoute)
            ->defaults('routerData', $routerData)
            ->defaults('provider', $this->name)
            ->middleware($routerData->middleware);
    }

    public function prepare(RouterData $routerData): void
    {
        $routePrefix = $routerData->getRoutePrefix();

        $this->loginRoute = $routePrefix.'login.'.$this->name;
        $this->callbackRoute = $routePrefix.'callback.'.$this->name;
        $this->backChannelLogoutRoute = $routePrefix.'logout.'.$this->name.'.back-channel';

        Config::set('services.'.$this->name.'.redirect', $this->getRedirectUrl($routerData));
    }

    public function getRedirectUrl(RouterData $routerData): string
    {
        return url($routerData->getUriPrefix().$this->callbackUri);
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
