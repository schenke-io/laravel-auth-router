<?php

namespace SchenkeIo\LaravelAuthRouter\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\SocialiteServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;
use SchenkeIo\LaravelAuthRouter\AuthRouterServiceProvider;

use function Orchestra\Testbench\workbench_path;

class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithWorkbench;

    // This trait automatically runs migrations

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Workbench\App\Providers\WorkbenchServiceProvider::class,
            AuthRouterServiceProvider::class,
            SocialiteServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:fH2Oq7m5N7hR7PzE6U6vR5vX6T7R5zE6U6vR5vX6T7R=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        foreach (['google', 'facebook', 'amazon', 'microsoft', 'paypal', 'auth0', 'stripe', 'linkedin', 'apple'] as $driver) {
            $app['config']->set('services.'.$driver, [
                'client_id' => 'fake-id',
                'client_secret' => 'fake-secret',
                'redirect' => 'fake-redirect',
            ]);
        }
        $app['config']->set('services.whatsapp', [
            'api_key' => 'fake-api-key',
            'approved_emails' => 'test@example.com',
        ]);
        $app['config']->set('services.workos_google', [
            'client_id' => 'fake-id',
            'api_key' => 'fake-api-key',
            'organization_id' => 'fake-org-id',
            'redirect' => 'fake-redirect',
        ]);
        $app['config']->set('services.workos_apple', [
            'client_id' => 'fake-id',
            'api_key' => 'fake-api-key',
            'organization_id' => 'fake-org-id',
            'redirect' => 'fake-redirect',
        ]);
        $app['config']->set('services.workos_email', [
            'client_id' => 'fake-id',
            'api_key' => 'fake-api-key',
            'organization_id' => 'fake-org-id',
            'redirect' => 'fake-redirect',
        ]);
        $app['config']->set('services.workos_linkedin', [
            'client_id' => 'fake-id',
            'api_key' => 'fake-api-key',
            'organization_id' => 'fake-org-id',
            'redirect' => 'fake-redirect',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(workbench_path('database/migrations'));
    }
}
