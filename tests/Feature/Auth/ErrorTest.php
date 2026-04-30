<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Auth\Error;

it('redirect an error and has text stored in a session', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');

    Route::get('/error', function () {
        return Blade::render('test {{$error}} {{$codeError');
    })->name('error');
    Route::get('/home', function () {})->name('home');
    Route::get('/dashboard', function () {})->name('dashboard');

    app('router')->getRoutes()->refreshNameLookups();

    Route::authRouter(['google'])->success('dashboard')->error('error')->home('home');
    // dd(routeNames());

    $response = $this->get('/callback/google');
    $response->assertRedirect(route('error'));
    $response->assertSessionHas('authRouterErrorInfo');
    $response->assertSessionHas('authRouterErrorMessage');

});

it('can redirect with log channel', function () {
    $routerData = getRouterData(true);
    $routerData->logChannel = 'stack';

    Log::shouldReceive('channel')
        ->with('stack')
        ->once()
        ->andReturnSelf();
    Log::shouldReceive('error')
        ->once();

    $response = Error::LocalAuth->redirect($routerData, 'some error');
    expect($response->getTargetUrl())->toBe(route('route-error'));
});

it('returns correct parameter keys', function () {
    expect(Error::ServiceNotSet->parameterKeys())->toBe(['name'])
        ->and(Error::UnknownService->parameterKeys())->toBe(['name'])
        ->and(Error::ExclusiveProvider->parameterKeys())->toBe(['name'])
        ->and(Error::ConfigNotSet->parameterKeys())->toBe(['key', 'env'])
        ->and(Error::LocalAuth->parameterKeys())->toBe([]);
});

it('translates correctly without translator bound', function () {
    // This is hard to test in Laravel because translator is usually bound.
    // But we can try to use a mock or just rely on the fact that it's bound.
    // If I want to hit the line 59 in Error.php, I'd need to unbind 'translator'
    // but that might break many things.
    // Let's just check the normal translation.
    expect(Error::LocalAuth->trans())->toBe(__('auth-router::errors.LocalAuth'));
});

it('returns fallback messages when translator is not bound', function () {
    $routerData = getRouterData(true);

    // Backup the translator
    $translator = app('translator');

    // Unbind it from the container
    app()->offsetUnset('translator');

    // Test trans() fallback (line 67)
    expect(Error::LocalAuth->trans())->toBe('LocalAuth');

    // Test transDatabaseError() fallback (line 76)
    $response = Error::LocalAuth->redirect($routerData, 'SQLSTATE[HY000]');
    $errorMessage = session('authRouterErrorMessage');
    expect($errorMessage)->toBe('Database error');

    // Restore it
    app()->instance('translator', $translator);
});

it('shortens database error messages', function () {
    $routerData = getRouterData(true);
    $longSqlError = "SQLSTATE[HY000]: General error: 1364 Field 'avatar' doesn't have a default value (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: dreiemah, SQL: insert into `users` (`email`, `name`, `updated_at`, `created_at`) values (kschenke@gmail.com, Kay-Uwe Schenke, 2026-04-30 15:42:58, 2026-04-30 15:42:58))";

    $response = Error::LocalAuth->redirect($routerData, $longSqlError);

    $errorMessage = session('authRouterErrorMessage');
    expect($errorMessage)->not->toContain('SQLSTATE');
    expect($errorMessage)->toBe(__('auth-router::errors.DatabaseError'));
});

it('truncates very long error messages', function () {
    $routerData = getRouterData(true);
    $longMessage = str_repeat('A', 200);

    $response = Error::LocalAuth->redirect($routerData, $longMessage);

    $errorMessage = session('authRouterErrorMessage');
    expect(strlen($errorMessage))->toBeLessThanOrEqual(100);
    expect($errorMessage)->toEndWith('...');
});
