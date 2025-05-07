<?php

namespace SchenkeIo\LaravelAuthRouter\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\SocialiteServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use SchenkeIo\LaravelAuthRouter\AuthRouterServiceProvider;

use function Orchestra\Testbench\workbench_path;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    // This trait automatically runs migrations

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            AuthRouterServiceProvider::class,
            SocialiteServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(workbench_path('database/migrations'));
    }
}
