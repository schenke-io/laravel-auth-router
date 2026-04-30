<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\GoogleProvider;
use Workbench\App\Models\User;

it('can handle stateless login', function () {
    // Set stateless config to true
    $this->app->config->set('services.google.stateless', true);
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');
    $this->app->config->set('auth.providers.users.model', User::class);

    // Test login method with stateless
    $redirectUrl = 'http://provider.auth.url';
    $redirectResponse = new RedirectResponse($redirectUrl);

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('redirectUrl')->andReturnSelf();
    Socialite::shouldReceive('stateless')->andReturnSelf();
    Socialite::shouldReceive('redirect')->andReturn($redirectResponse);

    $provider = new GoogleProvider;
    $routerData = new RouterData('dashboard', 'home', 'home', true);
    $response = $provider->login($routerData);
    $this->assertEquals($redirectUrl, $response->getTargetUrl());
    $this->assertTrue($provider->isStateless);

    // Test callback method with stateless
    $socialiteId = 'provider-user-id';
    $name = 'Test User';
    $email = 'test@example.com';
    $avatar = 'https://example.com/avatar.jpg';

    Route::get('/', fn () => '')->name('home');
    Route::get('/dashboard', fn () => '')->name('dashboard');
    app('router')->getRoutes()->refreshNameLookups();

    $socialiteUserMock = Mockery::mock(SocialiteUser::class);
    $socialiteUserMock->shouldReceive('getId')->andReturn($socialiteId);
    $socialiteUserMock->shouldReceive('getName')->andReturn($name);
    $socialiteUserMock->shouldReceive('getEmail')->andReturn($email);
    $socialiteUserMock->shouldReceive('getAvatar')->andReturn($avatar);

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('redirectUrl')->andReturnSelf();
    Socialite::shouldReceive('stateless')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUserMock);

    $routerData = new RouterData('dashboard', 'home', 'home', true);
    $response = $provider->callback($routerData);

    $this->assertTrue(Auth::check());
    $this->assertEquals(1, Auth::user()->id);
    $this->assertCount(0, $provider->errors());
    $this->assertEquals('http://localhost/dashboard', $response->getTargetUrl());
});

it('can see users from the database', function () {
    expect(User::all())->toHaveCount(0);
    User::factory(2)->create();
    expect(User::all())->toHaveCount(2);
});

it('redirects to google for login', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');

    $redirectUrl = 'http://provider.auth.url';
    $redirectResponse = new RedirectResponse($redirectUrl);

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('redirectUrl')->andReturnSelf();
    Socialite::shouldReceive('redirect')->andReturn($redirectResponse);

    $provider = new GoogleProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);
    $response = $provider->login($routerData);
    $this->assertEquals($redirectUrl, $response->getTargetUrl());
    $this->assertCount(0, $provider->errors());
});

it('handles the return code and authenticates the user if possible', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');
    $this->app->config->set('auth.providers.users.model', User::class);

    $socialiteId = 'provider-user-id';
    $name = 'Test User';
    $email = 'test@example.com';
    $avatar = 'https://example.com/avatar.jpg';

    Route::get('/', fn () => '')->name('home');
    Route::get('/dashboard', fn () => '')->name('dashboard');
    app('router')->getRoutes()->refreshNameLookups();

    $socialiteUserMock = Mockery::mock(SocialiteUser::class);
    $socialiteUserMock->shouldReceive('getId')->andReturn($socialiteId);
    $socialiteUserMock->shouldReceive('getName')->andReturn($name);
    $socialiteUserMock->shouldReceive('getEmail')->andReturn($email);
    $socialiteUserMock->shouldReceive('getAvatar')->andReturn($avatar);

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('redirectUrl')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUserMock);

    $provider = new GoogleProvider;
    $routerData = new RouterData('dashboard', 'home', 'home', true);
    $response = $provider->callback($routerData);

    $this->assertTrue(Auth::check());
    $this->assertEquals(1, Auth::user()->id);
    $this->assertCount(0, $provider->errors());
    $this->assertEquals('http://localhost/dashboard', $response->getTargetUrl());
});

it('generates redirect url when config incomplete', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    // second key missing

    Route::get('/', fn () => '')->name('home');
    Route::get('/dashboard', fn () => '')->name('dashboard');
    app('router')->getRoutes()->refreshNameLookups();

    $provider = new GoogleProvider;
    $routerData = getRouterData(true);
    $response = $provider->callback($routerData);
    $this->assertEquals('LocalAuth', customErrorType($response));
});

it('can store errors', function () {
    $this->app->config->set('services.google', null);
    $provider = new GoogleProvider;
    $this->assertCount(1, $provider->errors());
});

it('catch exception in callback', function () {
    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('redirectUrl')->andReturnSelf();
    Socialite::shouldReceive('user')->andThrowExceptions([new Exception]);

    Route::get('/', fn () => '')->name('home');
    Route::get('/dashboard', fn () => '')->name('dashboard');
    Route::get('/error', fn () => '')->name('error');
    app('router')->getRoutes()->refreshNameLookups();
    $routerData = new RouterData('dashboard', 'error', 'home', true);

    $provider = new GoogleProvider;
    $response = $provider->callback($routerData);
    $this->assertEquals('http://localhost/error', $response->getTargetUrl());
});

it('renders payload view when code is fake_code', function () {
    Route::get('/dashboard', fn () => 'dashboard')->name('dashboard');
    Route::get('/error', fn () => 'error')->name('error');
    Route::get('/home', fn () => 'home')->name('home');

    $routerData = new RouterData('dashboard', 'error', 'home', true);

    $response = $this->get('/callback/google?code=fake_code');

    $response->assertStatus(200);
    $response->assertViewIs('auth-router::callback-payload');
    $response->assertViewHas('userData');
});
