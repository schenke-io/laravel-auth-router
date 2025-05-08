<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

enum Error
{
    case UnableToAddNewUsers;
    case EmailMissing;
    case InvalidEmail;
    case Auth;
    case State;
    case Network;
    case InvalidRequest;

    public function redirect(RouterData $routerData, string $codeErrorMessage = ''): RedirectResponse
    {
        return Redirect::route($routerData->routeError)
            ->with('authRouterErrorInfo', __('auth-router::errors.'.$this->name))
            ->with('authRouterErrorMessage', $codeErrorMessage)
            ->withHeaders(['X-Custom-Error-Type' => $this->name]);
    }
}
