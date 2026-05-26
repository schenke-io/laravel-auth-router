<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\Auth;

pest()->group('feature');

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Auth\SessionKey;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Workbench\App\Models\User;

it('registers impersonation routes when enabled', function () {
    Route::authRouter(['google'])
        ->canImpersonate('admin')
        ->name('imp.')
        ->prefix('imp')
        ->register();

    $routes = Route::getRoutes();
    expect($routes->hasNamedRoute('imp.impersonate.start'))->toBeTrue()
        ->and($routes->hasNamedRoute('imp.impersonate.stop'))->toBeTrue();
});

it('does not register impersonation routes when disabled', function () {
    Route::authRouter(['google'])
        ->name('no-imp.')
        ->prefix('no-imp')
        ->register();

    $routes = Route::getRoutes();
    expect($routes->hasNamedRoute('no-imp.impersonate.start'))->toBeFalse()
        ->and($routes->hasNamedRoute('no-imp.impersonate.stop'))->toBeFalse();
});

it('can start and stop impersonation', function () {
    Route::authRouter(['google'])
        ->canImpersonate('admin')
        ->register();

    // Define routes that are used as success/home
    Route::get('home', fn () => 'home')->name('home');

    Gate::define('admin', fn ($user) => $user->email === 'admin@example.com');

    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create(['email' => 'user@example.com']);

    // Start as admin
    $this->actingAs($admin)
        ->get(route('impersonate.start', $user->id))
        ->assertRedirect(route('home'));

    expect(auth()->id())->toBe($user->id)
        ->and(session(SessionKey::IMPERSONATOR_ID))->toBe($admin->id);

    // Stop impersonation
    $this->get(route('impersonate.stop'))
        ->assertRedirect(route('home'));

    expect(auth()->id())->toBe($admin->id)
        ->and(session()->has(SessionKey::IMPERSONATOR_ID))->toBeFalse();
});

it('prevents unauthorized users from starting impersonation', function () {
    Route::authRouter(['google'])
        ->canImpersonate('admin')
        ->register();

    Gate::define('admin', fn ($user) => $user->email === 'admin@example.com');

    $nonAdmin = User::factory()->create(['email' => 'non-admin@example.com']);
    $user = User::factory()->create(['email' => 'user@example.com']);

    $this->actingAs($nonAdmin)
        ->get(route('impersonate.start', $user->id))
        ->assertStatus(403);
});

it('protects impersonation from being overwritten by new login', function () {
    Route::get('home', fn () => 'home')->name('home');
    Route::get('login', fn () => 'login')->name('login');

    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create(['email' => 'user@example.com']);

    // Mock session to simulate active impersonation
    session()->put(SessionKey::IMPERSONATOR_ID, $admin->id);
    auth()->login($user);

    $userData = new UserData(
        name: 'Another User',
        email: 'another@example.com',
        provider: 'google'
    );

    $routerData = new RouterData(
        routeSuccess: 'home',
        routeError: 'login',
        routeHome: 'home',
        impersonateGate: 'admin'
    );

    $response = $userData->authAndRedirect($routerData);

    // Should redirect to success route without logging in as "Another User"
    expect($response->getTargetUrl())->toBe(route('home'));
    expect(auth()->id())->toBe($user->id); // Still impersonated user
});
