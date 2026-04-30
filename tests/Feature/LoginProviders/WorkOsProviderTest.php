<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\LoginProviders;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Mockery;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\WorkosProvider;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;
use Workbench\App\Models\User;
use WorkOS\UserManagement;

class WorkOsProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->config->set('services.workos.client_id', 'workos_client_id');
        $this->app->config->set('services.workos.api_key', 'workos_api_key');
        $this->app->config->set('services.workos.client_secret', 'workos_client_secret');
        $this->app->config->set('services.workos.redirect', 'http://localhost/callback/workos');
        $this->app->config->set('auth.providers.users.model', User::class);

        Route::get('/', fn () => '')->name('home');
        Route::get('/dashboard', fn () => '')->name('dashboard');
        Route::get('/error', fn () => '')->name('error');
        app('router')->getRoutes()->refreshNameLookups();
    }

    public function test_it_redirects_to_workos_for_login()
    {
        $userManagementMock = Mockery::mock(UserManagement::class);
        $userManagementMock->shouldReceive('getAuthorizationUrl')
            ->once()
            ->andReturn('https://auth.workos.com/oauth/authorize');
        $this->app->instance(UserManagement::class, $userManagementMock);

        $provider = new WorkosProvider;
        $routerData = new RouterData('dashboard', 'error', 'home', true);

        $response = $provider->login($routerData);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://auth.workos.com/oauth/authorize', $response->getTargetUrl());
    }

    public function test_it_handles_callback_and_authenticates_user()
    {
        $provider = new WorkosProvider;
        $routerData = new RouterData('dashboard', 'error', 'home', true);

        $user = new \stdClass;
        $user->id = 'user_123';
        $user->email = 'test@example.com';
        $user->firstName = 'Test';
        $user->lastName = 'User';
        $user->profilePictureUrl = 'https://example.com/avatar.jpg';

        $responseObj = new \stdClass;
        $responseObj->user = $user;

        // Mocking UserManagement
        $userManagementMock = Mockery::mock(UserManagement::class);
        $userManagementMock->shouldReceive('authenticateWithCode')
            ->once()
            ->with('workos_client_id', 'valid_code')
            ->andReturn($responseObj);
        $this->app->instance(UserManagement::class, $userManagementMock);

        $this->app->instance('request', Request::create('/callback/workos', 'GET', ['code' => 'valid_code']));

        $response = $provider->callback($routerData);

        $this->assertTrue(Auth::check());
        $this->assertEquals('test@example.com', Auth::user()->email);
        $this->assertEquals('http://localhost/dashboard', $response->getTargetUrl());
    }

    public function test_it_handles_invalid_callback()
    {
        $provider = new WorkosProvider;
        $routerData = new RouterData('dashboard', 'error', 'home', true);
        $this->app->instance('request', Request::create('/callback/workos', 'GET')); // no code

        $response = $provider->callback($routerData);

        $this->assertEquals('http://localhost/error', $response->getTargetUrl());
    }

    public function test_is_social()
    {
        $provider = new WorkosProvider;
        $this->assertTrue($provider->isSocial());
    }

    public function test_callback_exception()
    {
        $provider = new WorkosProvider;
        $routerData = new RouterData('dashboard', 'error', 'home', true);

        $userManagementMock = Mockery::mock(UserManagement::class);
        $userManagementMock->shouldReceive('authenticateWithCode')
            ->once()
            ->andThrow(new \Exception('WorkOS Error'));
        $this->app->instance(UserManagement::class, $userManagementMock);

        $this->app->instance('request', Request::create('/callback/workos', 'GET', ['code' => 'error_code']));

        $response = $provider->callback($routerData);

        $this->assertEquals('http://localhost/error', $response->getTargetUrl());
        $this->assertEquals('RemoteAuth', customErrorType($response));
    }
}
