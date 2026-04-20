<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

use Illuminate\Http\RedirectResponse;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use WorkOS\UserManagement;

/**
 * Social login with WorkOS
 *
 * @link https://workos.com/docs/user-management
 */
class WorkosProvider extends BaseProvider
{
    /**
     * @return array<string,string>
     */
    public function env(): array
    {
        return [
            'client_id' => 'WORKOS_CLIENT_ID',
            'api_key' => 'WORKOS_API_KEY',
            'client_secret' => 'WORKOS_CLIENT_SECRET',
        ];
    }

    public function isSocial(): bool
    {
        return true;
    }

    public function login(RouterData $routerData): RedirectResponse
    {
        $request = request();
        $clientId = config('services.workos.client_id');
        $redirectUri = config('services.workos.redirect');

        $authorizationUrl = app(UserManagement::class)->getAuthorizationUrl(
            $redirectUri,
            null, // state
            'authkit', // provider
            null, // connection
            null, // organization
            null, // invitation
            $clientId
        );

        return redirect($authorizationUrl);
    }

    public function callback(RouterData $routerData): RedirectResponse
    {
        $request = request();
        $code = $request->query('code');
        if (! $code) {
            return Error::InvalidRequest->redirect($routerData);
        }

        try {
            $clientId = config('services.workos.client_id');
            $response = app(UserManagement::class)->authenticateWithCode(
                $clientId,
                $code
            );

            return UserData::fromWorkOs($response->user, $this->getProviderIdField())->authAndRedirect($routerData);
        } catch (\Exception $e) {
            return Error::RemoteAuth->redirect($routerData, $e->getMessage());
        }
    }
}
