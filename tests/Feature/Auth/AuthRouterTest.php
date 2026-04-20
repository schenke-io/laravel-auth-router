<?php

use Illuminate\Support\Facades\Route;
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

it('does not allow a guest to access the logout route', function () {
    Route::authRouter(['google'])->success('home')->error('error')->home('home');

    // Make a POST request to the logout route without authenticating
    $response = $this->post(route('logout'));

    // Assert that the guest is redirected to the login page
    $response->assertRedirect(route('login')); // Assuming your login route is named 'login'
});
