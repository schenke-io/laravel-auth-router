<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\Auth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Auth\ImpersonationController;
use SchenkeIo\LaravelAuthRouter\Auth\SessionKey;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Workbench\App\Models\User;

uses(LazilyRefreshDatabase::class);

pest()->group('feature');

it('registers impersonation routes only when enabled', function () {
    // Enabled
    Route::authRouter(['google'])->canImpersonate('admin')->name('imp.')->register();
    $routes = Route::getRoutes();
    expect($routes->hasNamedRoute('imp.impersonate.start'))->toBeTrue()
        ->and($routes->hasNamedRoute('imp.impersonate.stop'))->toBeTrue();

    // Disabled
    Route::authRouter(['google'])->name('no-imp.')->register();
    $routes = Route::getRoutes();
    expect($routes->hasNamedRoute('no-imp.impersonate.start'))->toBeFalse()
        ->and($routes->hasNamedRoute('no-imp.impersonate.stop'))->toBeFalse();
});

it('can start and stop impersonation', function () {
    Route::authRouter(['google'])->canImpersonate('admin')->register();
    Route::get('home', fn () => 'home')->name('home');
    Gate::define('admin', fn ($user) => $user->email === 'admin@example.com');

    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create(['email' => 'user@example.com']);

    // Start impersonation (GET)
    $this->actingAs($admin)
        ->get(route('impersonate.start', $user->id))
        ->assertRedirect(route('home'));

    expect(auth()->id())->toBe($user->id)
        ->and(session(SessionKey::IMPERSONATOR_ID))->toBe($admin->id);

    // Stop impersonation (POST)
    $this->post(route('impersonate.stop'))
        ->assertRedirect(route('home'));

    expect(auth()->id())->toBe($admin->id)
        ->and(session()->has(SessionKey::IMPERSONATOR_ID))->toBeFalse();
});

it('forgets the impersonator remember-me cookie when starting impersonation', function () {
    Route::authRouter(['google'])->canImpersonate('admin')->register();
    Route::get('home', fn () => 'home')->name('home');
    Gate::define('admin', fn ($user) => $user->email === 'admin@example.com');

    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $user = User::factory()->create(['email' => 'user@example.com']);

    $recallerName = 'remember_web_'.sha1(SessionGuard::class);

    $response = $this->actingAs($admin)
        ->get(route('impersonate.start', $user->id))
        ->assertRedirect(route('home'));

    // A deletion (past-expiry) cookie must be queued for the impersonator's recaller,
    // so a later session miss cannot silently restore the admin and end impersonation.
    $cookie = collect($response->headers->getCookies())
        ->first(fn ($c) => $c->getName() === $recallerName);

    expect($cookie)->not->toBeNull()
        ->and($cookie->getExpiresTime())->toBeLessThan(time());

    expect(auth()->id())->toBe($user->id);
});

it('enforces security on impersonation routes', function () {
    Route::authRouter(['google'])->canImpersonate('admin')->register();
    Gate::define('admin', fn ($user) => $user->email === 'admin@example.com');

    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $nonAdmin = User::factory()->create(['email' => 'non-admin@example.com']);
    $user = User::factory()->create(['email' => 'user@example.com']);

    // Start: 403 if gate fails
    $this->actingAs($nonAdmin)
        ->get(route('impersonate.start', $user->id))
        ->assertStatus(403);

    // Stop: 302 (Redirect to login) if not authenticated
    auth()->logout();
    Route::get('login', fn () => 'login')->name('login');
    $this->post(route('impersonate.stop'))
        ->assertRedirect(route('login'));

    // Stop: 405 if using GET instead of POST
    $this->actingAs($admin)
        ->get(route('impersonate.stop'))
        ->assertStatus(405);
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

it('skips forgetting recaller cookie if guard is not a SessionGuard', function () {
    $routerData = new RouterData(
        routeSuccess: 'home',
        routeError: 'login',
        routeHome: 'home',
        impersonateGate: 'admin'
    );

    $admin = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);
    Route::get('home', fn () => 'home')->name('home');

    $request = Request::create('/impersonate/'.$user->id, 'GET');
    $request->setLaravelSession(app('session.store'));

    $mockGuard = mock(Guard::class)->shouldIgnoreMissing();
    Auth::shouldReceive('guard')->andReturn($mockGuard);
    Auth::shouldReceive('id')->andReturn($admin->id);
    Auth::shouldReceive('loginUsingId')->once()->with((string) $user->id)->andReturn(true);
    Auth::makePartial();

    $controller = new ImpersonationController;
    $response = $controller->start($request, (string) $user->id, $routerData);

    expect($response->getTargetUrl())->toBe(route('home'));
});
