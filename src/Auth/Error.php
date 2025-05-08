<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

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

    /**
     * @param  array<string,string>  $errorParameter
     */
    public function redirect(RouterData $routerData, string $codeErrorMessage = '', array $errorParameter = []): RedirectResponse
    {
        return Redirect::route($routerData->routeError)
            ->with('authRouterErrorInfo', $this->trans($errorParameter))
            ->with('authRouterErrorMessage', $codeErrorMessage)
            ->withHeaders(['X-Custom-Error-Type' => $this->name]);
    }

    /**
     * @param  array<string,string>  $parameter
     */
    public function trans(array $parameter = []): string
    {
        return __('auth-router::errors.'.$this->name, $parameter);
    }

    /**
     * @return string[]
     */
    public function parameterKeys(): array
    {
        return match ($this) {
            self::ServiceNotSet, self::UnknownService => ['name'],
            self::ConfigNotSet => ['key'],
            default => [],
        };

    }
}
