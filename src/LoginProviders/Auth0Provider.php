<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

use Auth0\SDK\Auth0;
use Auth0\SDK\Exception\ConfigurationException;
use Auth0\SDK\Exception\NetworkException;
use Auth0\SDK\Exception\StateException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use SchenkeIo\LaravelAuthRouter\Contracts\UseExclusiveInterface;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Data\UserData;

/**
 * social login with Auth0
 *
 * Go to Auth0 Dashboard, navigate to "Applications," create a new Application, find Client ID and Secret on the "Settings" tab under "Basic Information."
 *
 * @link https://auth0.com/developers
 */
class Auth0Provider extends BaseProvider implements UseExclusiveInterface
{
    /**
     * key: expected key in config(system), value: suggested name ov ENV key,
     * used in testing and in documentation
     *
     * @return array<string,string>
     */
    public function env(): array
    {
        return [
            'client_id' => 'AUTH0_CLIENT_ID',
            'client_secret' => 'AUTH0_CLIENT_SECRET',
            'domain' => 'AUTH0_DOMAIN',
            'cookie_secret' => 'AUTH0_COOKIE_SECRET',
        ];
    }

    public function isSocial(): bool
    {
        return true;
    }

    /**
     * redirect to the provider login page
     *
     * @throws ConfigurationException
     */
    public function login(RouterData $routerData): RedirectResponse
    {
        $auth0 = app(Auth0::class);
        $request = request();
        $redirectUri = $this->getRedirectUrl();

        return redirect($auth0->login($redirectUri, ['login_hint' => $request->get('hint')]));
    }

    /**
     * handles the return code and authenticate the user if possible
     */
    public function callback(RouterData $routerData): RedirectResponse
    {
        $auth0 = app(Auth0::class);
        $request = request();
        $requestData = $request->all();
        $hasAuthenticated = isset($requestData['state']) && isset($requestData['code']);
        $hasAuthenticationFailure = isset($requestData['error']);

        if ($hasAuthenticated) {
            try {
                $auth0->exchange();
                $auth0User = $auth0->getUser();
                if (! $auth0User) {
                    return Error::LocalAuth->redirect($routerData);
                }

                return UserData::fromAuth0($auth0User)->authAndRedirect($routerData);
            } catch (NetworkException $e) {
                return Error::Network->redirect($routerData, $e->getMessage());
            } catch (StateException $e) {
                return Error::State->redirect($routerData, $e->getMessage());
            }
        } elseif ($hasAuthenticationFailure) {
            return Error::RemoteAuth->redirect($routerData, $requestData['error']);
        } else {
            return Error::InvalidRequest->redirect($routerData);
        }
    }
}
