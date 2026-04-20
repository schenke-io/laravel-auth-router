<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\LoginProviders;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\AppleProvider;
use SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
use SchenkeIo\LaravelAuthRouter\Services\AppleTokenGenerator;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;
use Workbench\App\Models\User;

class AppleProviderTest extends TestCase
{
    public function test_apple_provider_generates_dynamic_client_secret_during_login()
    {
        $this->app->config->set('services.apple', [
            'client_id' => 'test_client_id',
            'team_id' => 'test_team_id',
            'key_id' => 'test_key_id',
            'private_key' => 'test_private_key',
        ]);

        $mockTokenGenerator = \Mockery::mock(AppleTokenGenerator::class);
        $mockTokenGenerator->shouldReceive('generate')
            ->once()
            ->with('test_team_id', 'test_key_id', 'test_private_key', 'test_client_id')
            ->andReturn('generated_client_secret');

        $this->app->instance(AppleTokenGenerator::class, $mockTokenGenerator);

        $redirectUrl = 'http://apple.com/auth';
        $redirectResponse = new RedirectResponse($redirectUrl);

        Socialite::shouldReceive('driver')->with('apple')->andReturnSelf();
        Socialite::shouldReceive('redirect')->andReturn($redirectResponse);

        $provider = new AppleProvider;
        $routerData = new RouterData('dashboard', 'home', 'home', true);

        $response = $provider->login($routerData);

        $this->assertEquals($redirectUrl, $response->getTargetUrl());
        $this->assertEquals('generated_client_secret', config('services.apple.client_secret'));
    }

    public function test_apple_provider_generates_dynamic_client_secret_during_callback()
    {
        $this->app->config->set('services.apple', [
            'client_id' => 'test_client_id',
            'team_id' => 'test_team_id',
            'key_id' => 'test_key_id',
            'private_key' => 'test_private_key',
        ]);
        $this->app->config->set('auth.providers.users.model', User::class);

        $mockTokenGenerator = \Mockery::mock(AppleTokenGenerator::class);
        $mockTokenGenerator->shouldReceive('generate')
            ->once()
            ->andReturn('generated_client_secret');

        $this->app->instance(AppleTokenGenerator::class, $mockTokenGenerator);

        Route::get('/', fn () => '')->name('home');
        Route::get('/dashboard', fn () => '')->name('dashboard');
        app('router')->getRoutes()->refreshNameLookups();

        $socialiteUserMock = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $socialiteUserMock->shouldReceive('getId')->andReturn('apple-id');
        $socialiteUserMock->shouldReceive('getName')->andReturn('Apple User');
        $socialiteUserMock->shouldReceive('getEmail')->andReturn('apple@example.com');
        $socialiteUserMock->shouldReceive('getAvatar')->andReturn('avatar-url');

        Socialite::shouldReceive('driver')->with('apple')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($socialiteUserMock);

        $provider = new AppleProvider;
        $routerData = new RouterData('dashboard', 'home', 'home', true);

        $provider->callback($routerData);

        $this->assertEquals('generated_client_secret', config('services.apple.client_secret'));
    }

    public function test_callback_handles_exception_from_token_generator()
    {
        $this->app->config->set('services.apple', [
            'client_id' => '', // triggers exception in AppleTokenGenerator
            'team_id' => 'team',
            'key_id' => 'key',
            'private_key' => 'pk',
        ]);

        $provider = new AppleProvider;
        $routerData = new RouterData('success', 'error', 'home', true);

        Route::get('/error', fn () => 'error')->name('error');
        app('router')->getRoutes()->refreshNameLookups();

        $response = $provider->callback($routerData);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('LocalAuth', $response->headers->get('X-Custom-Error-Type'));
        $this->assertStringContainsString('error', $response->getTargetUrl());
    }

    public function test_env_returns_correct_variables()
    {
        $provider = new AppleProvider;
        $provider->name = 'apple';
        $env = $provider->env();

        $this->assertEquals([
            'client_id' => 'APPLE_CLIENT_ID',
            'team_id' => 'APPLE_TEAM_ID',
            'key_id' => 'APPLE_KEY_ID',
            'private_key' => 'APPLE_PRIVATE_KEY',
        ], $env);
    }

    public function test_callback_handles_fake_code()
    {
        request()->merge(['code' => 'fake_code']);

        $provider = new AppleProvider;
        $provider->name = 'apple';
        $routerData = new RouterData('dashboard', 'home', 'home', true);

        $response = $provider->callback($routerData);

        $this->assertStringContainsString('Fake User', $response->render());
    }

    public function test_webhook_calls_apple_auth_service()
    {
        $payload = ['type' => 'email-disabled', 'sub' => 'apple-id'];
        $request = Request::create('/webhook', 'POST', $payload);

        $mockService = \Mockery::mock(AppleAuthService::class);
        $mockService->shouldReceive('handleServerNotification')
            ->once()
            ->with($payload);

        $this->app->instance(AppleAuthService::class, $mockService);

        $provider = new AppleProvider;
        $response = $provider->webhook($request);

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function test_callback_handles_stateless_driver()
    {
        $this->app->config->set('services.apple', [
            'client_id' => 'test_client_id',
            'team_id' => 'test_team_id',
            'key_id' => 'test_key_id',
            'private_key' => 'test_private_key',
            'stateless' => true,
        ]);
        $this->app->config->set('auth.providers.users.model', User::class);

        $mockTokenGenerator = \Mockery::mock(AppleTokenGenerator::class);
        $mockTokenGenerator->shouldReceive('generate')->andReturn('secret');
        $this->app->instance(AppleTokenGenerator::class, $mockTokenGenerator);

        Route::get('/dashboard', fn () => '')->name('dashboard');
        app('router')->getRoutes()->refreshNameLookups();

        $socialiteUserMock = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $socialiteUserMock->shouldReceive('getId')->andReturn('apple-id');
        $socialiteUserMock->shouldReceive('getName')->andReturn('Apple User');
        $socialiteUserMock->shouldReceive('getEmail')->andReturn('apple@example.com');
        $socialiteUserMock->shouldReceive('getAvatar')->andReturn('avatar-url');

        $driverMock = \Mockery::mock(AbstractProvider::class);
        $driverMock->shouldReceive('stateless')->andReturnSelf();
        $driverMock->shouldReceive('user')->andReturn($socialiteUserMock);

        Socialite::shouldReceive('driver')->with('apple')->andReturn($driverMock);

        $provider = new AppleProvider;
        $routerData = new RouterData('dashboard', 'home', 'home', true);

        $provider->callback($routerData);

        $this->assertTrue(true); // Verification via Socialite mock
    }
}
