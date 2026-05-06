<?php

use Illuminate\Support\Facades\Route;
use Logto\Sdk\LogtoClient;
use SchenkeIo\LaravelAuthRouter\Auth\SessionKey;
use Workbench\App\Models\User;

beforeEach(function () {
    Route::clearResolvedInstances();
});

it('adds a login route', function () {
    // Define a dummy route that the provider's loginRoute will point to
    Route::get('login/google', fn () => 'Provider Login Page')->name('login.google');

    Route::authRouter(['google'])->success('home')->error('error')->home('home');

    // find the route
    $route = collect(Route::getRoutes()->getIterator())->first(fn ($route) => $route->getName() === 'login');
    $this->assertNotNull($route, 'The "login" route was not found in the route collection.');
});

it('allows an authenticated user to log out and redirects to the home route', function () {
    // Create a user and authenticate them
    $user = User::factory()->create();
    $this->actingAs($user);

    // define home
    Route::get('/', fn () => '')->name('home');

    Route::authRouter(['google'])->success('home')->error('error')->home('home');

    // Make a POST request to the logout route with authenticating
    $response = $this->post(route('logout'));

    // Assert that the guest is redirected to the login page
    $response->assertRedirect(route('home'));

    // Assert that the user is no longer authenticated (optional but recommended)
    $this->assertGuest();

});

it('redirects to logto logout if logto is in session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->app->config->set('services.logto', [
        'endpoint' => 'https://logto.example.com',
        'app_id' => 'app_id',
        'app_secret' => 'app_secret',
    ]);

    Route::get('/the-home-path', fn () => '')->name('the-home-route');
    app('router')->getRoutes()->refreshNameLookups();

    session([SessionKey::PROVIDER => 'logto']);

    $mockClient = Mockery::mock(LogtoClient::class);
    $mockClient->shouldReceive('signOut')->andReturn('https://logto.example.com/logout');

    app()->bind(LogtoClient::class, fn () => $mockClient);

    Route::authRouter(['logto'])->success('the-home-route')->error('error')->home('the-home-route');

    $response = $this->post(route('logout'));

    $response->assertRedirect('https://logto.example.com/logout');
    $this->assertGuest();
});

it('does not allow a guest to access the logout route', function () {
    Route::authRouter(['google'])->success('home')->error('error')->home('home');

    // Make a POST request to the logout route without authenticating
    $response = $this->post(route('logout'));

    // Assert that the guest is redirected to the login page
    $response->assertRedirect(route('login')); // Assuming your login route is named 'login'
});
