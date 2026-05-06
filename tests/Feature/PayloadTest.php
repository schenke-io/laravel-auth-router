<?php

use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Auth\SessionKey;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Workbench\App\Models\User;

it('always registers payload routes', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');

    Route::authRouter('google')
        ->success('success')
        ->error('error');

    $routeNames = collect(Route::getRoutes())->map->getName()->toArray();

    expect($routeNames)->toContain('callback.payload')
        ->toContain('callback.finalize');
});

it('redirects to payload view when showPayload is enabled', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');
    $this->app->config->set('auth.providers.users.model', User::class);

    Route::authRouter('google')
        ->success('success-route')
        ->error('error-route')
        ->showPayload();

    Route::get('/success', fn () => 'success')->name('success-route');

    $userData = new UserData(
        name: 'John Doe',
        email: 'john@example.com',
        avatar: 'https://example.com/avatar.jpg',
        provider: 'google'
    );
    $routerData = new RouterData(
        routeSuccess: 'success-route',
        routeError: 'error-route',
        routeHome: 'home',
        showPayload: true
    );

    $response = $userData->authAndRedirect($routerData);

    expect($response->getTargetUrl())->toBe(route('callback.payload'));
    expect(session(SessionKey::PAYLOAD))->toBe($userData);
});

it('can finalize login from payload session', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');
    $this->app->config->set('auth.providers.users.model', User::class);

    User::factory()->create(['email' => 'john@example.com']);

    Route::get('/success', fn () => 'success')->name('success-route');
    Route::get('/', fn () => 'home')->name('home');

    Route::authRouter('google')
        ->success('success-route')
        ->error('error-route')
        ->showPayload();

    $userData = new UserData(
        name: 'John Doe',
        email: 'john@example.com',
        avatar: 'https://example.com/avatar.jpg',
        provider: 'google'
    );
    session([SessionKey::PAYLOAD => $userData]);

    $response = $this->post(route('callback.finalize'));

    $response->assertRedirect(route('success-route'));
    $this->assertAuthenticated();
    expect(session(SessionKey::PAYLOAD))->toBeNull();
});
