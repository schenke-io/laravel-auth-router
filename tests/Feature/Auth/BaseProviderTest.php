<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\Auth;

use Illuminate\Support\Facades\Log;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\GoogleProvider;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;

class BaseProviderTest extends TestCase
{
    public function test_provider_with_missing_service_config()
    {
        // Remove google service config
        $this->app->config->set('services.google', null);

        $provider = new GoogleProvider;

        $this->assertFalse($provider->valid());
        // Dump for debugging
        // dd($provider->errors());
        $this->assertNotEmpty($provider->errors());
        $this->assertEquals('auth-router::provider.error', $provider->blade);
    }

    public function test_provider_with_missing_env_vars()
    {
        // Set service config but missing keys
        $this->app->config->set('services.google', [
            'client_id' => '',
            'client_secret' => '',
            'redirect' => '',
        ]);

        $provider = new GoogleProvider;

        $this->assertFalse($provider->valid());
        $this->assertCount(2, $provider->errors());
    }

    public function test_constructor_with_name()
    {
        $provider = new GoogleProvider('custom_google');
        // Service::get('custom_google') might return null if not defined,
        // but name should still be handled in BaseProvider constructor.
        // Actually BaseProvider:46: $this->name = $this->service->name ?? 'unknown';
        // If Service::get('custom_google') is null, name will be 'unknown'
        $this->assertEquals('unknown', $provider->name);
    }

    public function test_get_action()
    {
        $provider = new GoogleProvider;
        $this->assertEquals(GoogleProvider::class.'@myMethod', $provider->getAction('myMethod'));
    }

    public function test_logout_returns_null()
    {
        $provider = new class extends BaseProvider
        {
            public function env(): array
            {
                return [];
            }

            public function isSocial(): bool
            {
                return false;
            }

            public function login(RouterData $routerData): mixed
            {
                return null;
            }

            public function callback(RouterData $routerData): mixed
            {
                return null;
            }
        };
        $routerData = getRouterData(true);
        $this->assertNull($provider->logout($routerData));
    }

    public function test_log_method()
    {
        $provider = new GoogleProvider;
        $routerData = getRouterData(true);
        $routerData->logChannel = 'stack';

        Log::shouldReceive('channel')
            ->with('stack')
            ->once()
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->once();

        $provider->log($routerData, 'test message');
    }

    public function test_constructor_normalizes_string_config()
    {
        $this->app->config->set('services.google', 'just-the-client-id');
        $provider = new GoogleProvider;
        $this->assertEquals('just-the-client-id', config('services.google.client_id'));
        $this->assertTrue($provider->valid());
    }

    public function test_constructor_handles_missing_keys_in_mapped_config()
    {
        $this->app->config->set('services.google', 'just-the-client-id');
        // GoogleProvider's env() returns [client_id, client_secret]
        // If fromMapping is true, it only checks client_id
        $provider = new GoogleProvider;
        $this->assertTrue($provider->valid());
        $this->assertEmpty($provider->errors());
    }
}
