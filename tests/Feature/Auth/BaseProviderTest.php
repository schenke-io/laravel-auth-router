<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\Auth;

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

    public function test_get_provider_id_field()
    {
        $this->app->config->set('services.google.user_id_field', true);
        $provider = new GoogleProvider;
        $this->assertEquals('google_id', $provider->getProviderIdField());

        $this->app->config->set('services.google.user_id_field', false);
        $this->assertNull($provider->getProviderIdField());
    }
}
