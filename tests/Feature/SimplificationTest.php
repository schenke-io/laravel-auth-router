<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use SchenkeIo\LaravelAuthRouter\Auth\SessionKey;
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\GoogleProvider;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;
use SchenkeIo\LaravelAuthRouter\Traits\InteractsWithAuthRouter;
use Workbench\App\Models\User;

class SimpleUser extends User implements AuthenticatableRouterUser
{
    use InteractsWithAuthRouter;

    protected $table = 'simple_users';

    protected $fillable = ['name', 'email', 'google_id', 'apple_id'];
}

class SimplificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_come_back_route_exists()
    {
        $response = $this->get(route('only-whatsapp.login.come-back', ['path' => 'some/path']));
        $response->assertRedirect(route('only-whatsapp.login', ['come-back' => 'some/path']));
        $response->assertSessionHas(SessionKey::URL_INTENDED, '/some/path');
    }

    public function test_login_come_back_route_validates_path()
    {
        $response = $this->get(route('only-whatsapp.login.come-back', ['path' => 'http://malicious.com']));
        $response->assertStatus(400);

        $response = $this->get(route('only-whatsapp.login.come-back', ['path' => 'some/path?query=1']));
        $response->assertStatus(400);
    }

    public function test_login_return_route_exists()
    {
        $response = $this->from('/previous-page')->get(route('only-whatsapp.login.return'));
        $response->assertRedirect(route('only-whatsapp.login'));
        $response->assertSessionHas(SessionKey::URL_INTENDED, 'http://localhost/previous-page');
    }

    public function test_redirects_to_come_back_path_after_login()
    {
        $this->app->config->set('services.google.client_id', 'google_client_id');
        $this->app->config->set('services.google.client_secret', 'google_client_secret');
        $this->app->config->set('auth.providers.users.model', User::class);

        $socialiteUserMock = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $socialiteUserMock->shouldReceive('getId')->andReturn('123');
        $socialiteUserMock->shouldReceive('getName')->andReturn('Test');
        $socialiteUserMock->shouldReceive('getEmail')->andReturn('test@example.com');
        $socialiteUserMock->shouldReceive('getAvatar')->andReturn('https://avatar.com');

        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('redirectUrl')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($socialiteUserMock);

        session([SessionKey::URL_INTENDED => '/special-path']);

        $provider = new GoogleProvider;
        $routerData = new RouterData('success', 'home', 'home', true);

        $response = $provider->callback($routerData);

        $this->assertEquals('http://localhost/special-path', $response->getTargetUrl());
        $this->assertFalse(session()->has(SessionKey::URL_INTENDED));
    }
}
