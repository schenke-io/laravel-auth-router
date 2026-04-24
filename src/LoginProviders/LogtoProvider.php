<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

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

        return new LogtoClient(
            new LogtoConfig(
                endpoint: $config['endpoint'] ?? '',
                appId: $config['app_id'] ?? '',
                appSecret: $config['app_secret'] ?? '',
            ),
            new LogtoStorage
        );
    }

    public function login(RouterData $routerData): mixed
    {
        $client = $this->getClient();

        return redirect($client->signIn($this->getRedirectUrl()));
    }

    public function callback(RouterData $routerData): mixed
    {
        $client = $this->getClient();
        try {
            $client->handleSignInCallback();
            $claims = $client->getIdTokenClaims();

            $userData = new UserData(
                name: $claims->name ?? $claims->username ?? $claims->sub,
                email: $claims->email ?? '',
                avatar: $claims->picture,
                provider: $this->name
            );

            return $userData->authAndRedirect($routerData);
        } catch (\Exception $e) {
            return Error::LocalAuth->redirect($routerData, $e->getMessage());
        }
    }
}
