<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

use Illuminate\Http\RedirectResponse;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;

class UnknownBaseProvider extends BaseProvider
{
    /**
     * key: suggested name of ENV key, value: expected key in config(system)
     * used in testing and in documentation
     *
     * @return array<string,string>
     */
    public function env(): array
    {
        return [];
    }

    /**
     * redirect to the provider login page
     */
    public function login(string $redirectUri): RedirectResponse
    {
        return redirect()->back();
    }

    /**
     * handles the return code and authenticate the user if possible
     */
    public function callback(RouterData $routerData): RedirectResponse
    {
        return redirect()->back();
    }
}
