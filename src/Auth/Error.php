<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

/**
 * Enumeration for all login and configuration errors.
 */
enum Error
{
    case UnknownService;
    case ServiceNotSet;
    case ConfigNotSet;
    case UnableToAddNewUsers;
    case EmailMissing;
    case InvalidEmail;
    case LocalAuth;
    case RemoteAuth;
    case State;
    case Network;
    case InvalidRequest;
    case MixedProviders;
    case ExclusiveProvider;
    case InvalidCredentials;
    case InvalidToken;

    /**
     * @param  array<string,string>  $errorParameter
     */
    public function redirect(RouterData $routerData, string $codeErrorMessage = '', array $errorParameter = []): RedirectResponse
    {
        if ($routerData->logChannel) {
            Log::channel($routerData->logChannel)->error('AuthRouter error', [
                'type' => $this->name,
                'info' => $this->trans($errorParameter),
                'message' => $codeErrorMessage,
            ]);
        }

        return Redirect::route($routerData->routeError)
            ->withInput()
            ->with('authRouterErrorInfo', $this->trans($errorParameter))
            ->with('authRouterErrorMessage', $codeErrorMessage)
            ->withHeaders(['X-Custom-Error-Type' => $this->name]);
    }

    /**
     * @param  array<string,string>  $parameter
     */
    public function trans(array $parameter = []): string
    {
        if (app()->bound('translator')) {
            return __('auth-router::errors.'.$this->name, $parameter);
        }

        return $this->name;
    }

    /**
     * @return string[]
     */
    public function parameterKeys(): array
    {
        return match ($this) {
            self::ServiceNotSet, self::UnknownService, self::ExclusiveProvider => ['name'],
            self::ConfigNotSet => ['key', 'env'],
            default => [],
        };

    }
}
