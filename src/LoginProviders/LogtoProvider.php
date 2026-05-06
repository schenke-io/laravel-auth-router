<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

use Illuminate\Http\RedirectResponse;
use Logto\Sdk\Constants\UserScope;
use Logto\Sdk\LogtoClient;
use Logto\Sdk\LogtoConfig;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use SchenkeIo\LaravelAuthRouter\Contracts\UseExclusiveInterface;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use SchenkeIo\LaravelAuthRouter\Services\LogtoStorage;

class LogtoProvider extends BaseProvider implements UseExclusiveInterface
{
    public function env(): array
    {
        return [
            'endpoint' => 'LOGTO_ENDPOINT',
            'app_id' => 'LOGTO_APP_ID',
            'app_secret' => 'LOGTO_APP_SECRET',
        ];
    }

    public function isSocial(): bool
    {
        return true;
    }

    protected function getClient(): LogtoClient
    {
        $config = config('services.logto');

        return app(LogtoClient::class, [
            'config' => new LogtoConfig(
                endpoint: $config['endpoint'] ?? '',
                appId: $config['app_id'] ?? '',
                appSecret: $config['app_secret'] ?? '',
                scopes: [
                    UserScope::email,
                    UserScope::profile,
                ]  // must be explicitly named to avoid scope issues
            ),
            'storage' => new LogtoStorage,
        ]);
    }

    public function login(RouterData $routerData): mixed
    {
        $this->log($routerData, 'AuthRouter login start');
        $client = $this->getClient();

        return redirect($client->signIn($this->getRedirectUrl()));
    }

    public function callback(RouterData $routerData): mixed
    {
        $this->log($routerData, 'AuthRouter callback start');
        $client = $this->getClient();
        try {
            $client->handleSignInCallback();
            $claims = $client->getIdTokenClaims();

            $userData = new UserData(
                name: $claims->name ?? $claims->username ?? '',
                email: $claims->email ?? '',
                avatar: $claims->picture ?? '',
                provider: $this->name,
                providerId: $claims->sub,
                isExclusive: true
            );

            return $userData->authAndRedirect($routerData);
        } catch (\Exception $e) {
            return Error::LocalAuth->redirect($routerData, $e->getMessage());
        }
    }

    public function logout(RouterData $routerData): ?RedirectResponse
    {
        $client = $this->getClient();
        $signOutUrl = $client->signOut(url(route($routerData->routeHome)));

        return redirect($signOutUrl);
    }

    public function getIssuer(): ?string
    {
        $endpoint = config('services.logto.endpoint');

        return $endpoint ? rtrim($endpoint, '/').'/oidc' : null;
    }
}
