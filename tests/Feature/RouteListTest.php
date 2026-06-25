<?php

pest()->group('feature', 'routing');

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Data\EmailConfirmData;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Enums\Error;

it('can cache routes with multiple providers and groups', function () {
    Route::group(['prefix' => 'auth', 'as' => 'auth.', 'middleware' => 'web'], function () {
        Route::authRouter(['google', 'facebook'])
            ->name('social')
            ->register();
    });

    $this->artisan('route:cache')->assertExitCode(0);

    expect(Route::has('auth.social.login.google'))->toBeTrue()
        ->and(Route::has('auth.social.callback.google'))->toBeTrue();

    $this->artisan('route:clear')->assertExitCode(0);
})->todo();

it('can handle impersonation routes and cache', function () {
    Route::group(['middleware' => 'web'], function () {
        Route::authRouter(['google'])
            ->canImpersonate('admin')
            ->register();
    });

    $this->artisan('route:cache')->assertExitCode(0);

    expect(Route::has('impersonate.start'))->toBeTrue()
        ->and(Route::has('impersonate.stop'))->toBeTrue();

    $this->artisan('route:clear')->assertExitCode(0);
})->todo();

it('can handle requests while routes are cached', function () {
    Route::group(['middleware' => 'web'], function () {
        Route::authRouter(['google', 'facebook'])->register();
    });

    $this->artisan('route:cache')->assertExitCode(0);

    // Hit login index
    $this->get(route('login'))->assertStatus(200);

    // Hit a provider login route (should redirect)
    $this->get(route('login.google'))->assertStatus(302);

    $this->artisan('route:clear')->assertExitCode(0);
})->todo();

it('verifies emailConfirmClass survives caching', function () {
    $class = EmailConfirmData::class;
    Route::group(['middleware' => 'web'], function () use ($class) {
        Route::authRouter(['google', 'facebook'])
            ->emailConfirm(new $class(
                'test@example.com',
                'token',
                'First',
                'Last',
                Carbon::now()
            ))
            ->register();
    });

    $this->artisan('route:cache')->assertExitCode(0);

    $route = Route::getRoutes()->getByName('login');
    /** @var RouterData $routerData */
    $routerData = $route->defaults['routerData'];

    expect($routerData->emailConfirmClass)->toBe($class);

    $this->artisan('route:clear')->assertExitCode(0);
})->todo();

it('shows closure error on login index when routes are cached', function () {
    Route::group(['middleware' => 'web'], function () {
        Route::authRouter(['google', 'facebook'])->defaultName(fn ($u) => 'test')->register();
    });

    $this->artisan('route:cache')->assertExitCode(0);

    $response = $this->get(route('login'));
    $response->assertStatus(200);
    $response->assertSee(Error::ClosureNotCacheable->trans());

    $this->artisan('route:clear')->assertExitCode(0);
})->todo();
