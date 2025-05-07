<?php

use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Auth\AuthRouter;
use SchenkeIo\LaravelAuthRouter\Tests\Feature\Auth\DummyProvider;
use Workbench\App\Models\User;

beforeEach(function () {
    Route::clearResolvedInstances();
});

it('adds a login route', function () {
    $mockProvider = new DummyProvider;
    // Define a dummy route that the provider's loginRoute will point to
    Route::get('login/unknown', fn () => 'Provider Login Page')->name('login.unknown');
    // Instantiate the class containing the addLogin method
    $authRouter = new AuthRouter;
    // Call the method to register the login route
    $authRouter->addLoginRedirect($mockProvider);
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

    $authRouter = new AuthRouter;
    $authRouter->addLoginRedirect(new DummyProvider);  // sets the login route
    $authRouter->addLogout('home');  // sets the logout route

    // Make a POST request to the logout route with authenticating
    $response = $this->post(route('logout'));

    // Assert that the guest is redirected to the login page
    $response->assertRedirect(route('home'));

    // Assert that the user is no longer authenticated (optional but recommended)
    $this->assertGuest();

});

it('does not allow a guest to access the logout route', function () {
    $authRouter = new AuthRouter;
    $authRouter->addLoginRedirect(new DummyProvider);  // sets the login route
    $authRouter->addLogout('home');  // sets the logout route

    // Make a POST request to the logout route without authenticating
    $response = $this->post(route('logout'));

    // Assert that the guest is redirected to the login page
    $response->assertRedirect(route('login')); // Assuming your login route is named 'login'
});
