<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Events\BackChannelLogoutEvent;

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
        $this->loginRoute = 'login.'.$this->name;
        $this->callbackUri = 'callback/'.$this->name;
        $this->callbackRoute = 'callback.'.$this->name;
        $this->backChannelLogoutUri = 'logout/'.$this->name.'/back-channel';
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

    public function backChannelLogout(Request $request, RouterData $routerData): mixed
    {
        $tokenString = $request->input('logout_token');
        if (! is_string($tokenString) || $tokenString === '') {
            return response('Missing logout_token', 400);
        }

        try {
            $token = $this->parseToken($tokenString);

            if (! $token instanceof UnencryptedToken) {
                return response('Invalid token format', 400);
            }

            // 1. MUST NOT contain nonce
            if ($token->claims()->has('nonce')) {
                return response('Token contains nonce', 400);
            }

            // 2. MUST contain events claim
            $events = $token->claims()->get('events');
            if (! is_array($events) || ! isset($events['http://schemas.openid.net/event/backchannel-logout'])) {
                return response('Missing backchannel-logout event', 400);
            }

            // 3. MUST contain iat
            if (! $token->claims()->has('iat')) {
                return response('Missing iat claim', 400);
            }

            // 4. MUST contain sub or sid
            if (! $token->claims()->has('sub') && ! $token->claims()->has('sid')) {
                return response('Missing sub or sid', 400);
            }

            // 5. Validate issuer if possible
            $issuer = $this->getIssuer();
            if ($issuer && $token->claims()->get('iss') !== $issuer) {
                return response('Invalid issuer', 400);
            }

            // 5. Validate audience if possible
            $clientId = $this->getClientId();
            if ($clientId && ! in_array($clientId, (array) $token->claims()->get('aud'))) {
                return response('Invalid audience', 400);
            }

            // Optional: Signature validation would go here if we had the key

            $this->log($routerData, 'Back-channel logout received', [
                'sub' => $token->claims()->get('sub'),
                'sid' => $token->claims()->get('sid'),
            ]);

            BackChannelLogoutEvent::dispatch(
                $this->name,
                $token->claims()->get('sub'),
                $token->claims()->get('sid')
            );

            return response('OK', 200);

        } catch (\Exception $e) {
            return response('Invalid token: '.$e->getMessage(), 400);
        }
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
     * @param  non-empty-string  $tokenString
     */
    protected function parseToken(string $tokenString): Token
    {
        return (new Parser(new JoseEncoder))->parse($tokenString);
    }

    /**
     * @param  array<int, string>  $middleware
     */
    public function registerRoutes(RouterData $routerData, array $middleware): void
    {
        $uriPrefix = $routerData->getUriPrefix();
        $routePrefix = $routerData->getRoutePrefix();

        $this->loginRoute = $routePrefix.$this->loginRoute;
        $this->callbackRoute = $routePrefix.$this->callbackRoute;
        $this->backChannelLogoutRoute = $routePrefix.$this->backChannelLogoutRoute;

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

        Route::post($uriPrefix.$this->backChannelLogoutUri, fn (Request $request) => app()->call([$this, 'backChannelLogout'], ['routerData' => $routerData]))
            ->name($this->backChannelLogoutRoute)
            ->defaults('routerData', $routerData);
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
