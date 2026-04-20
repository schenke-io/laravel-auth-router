<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\WhatsappProvider;
use Workbench\App\Models\User;

beforeEach(function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $this->app->config->set('services.whatsapp', [
        'api_key' => 'fake-key',
        'approved_emails' => 'test@example.com,approved@example.com',
    ]);

    Route::get('/', fn () => 'home')->name('home');
    Route::get('/dashboard', fn () => 'dashboard')->name('dashboard');
    Route::get('/error', fn () => 'error')->name('error');
    Route::get('/login/whatsapp', fn () => 'login-whatsapp')->name('login.whatsapp');
    app('router')->getRoutes()->refreshNameLookups();
});

it('renders login view on GET', function () {
    $provider = new WhatsappProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);

    $response = $provider->login($routerData);

    expect($response->name())->toBe('auth-router::login');
});

it('fails login if email is missing', function () {
    $provider = new WhatsappProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);

    // Simulate POST request without email
    request()->merge(['email' => '']);
    request()->setMethod('POST');

    $response = $provider->login($routerData);

    expect($response->getTargetUrl())->toContain('login/whatsapp');
    expect(session('authRouterErrorMessage'))->toBe('Valid email is required');
});

it('fails login if email is not approved', function () {
    $provider = new WhatsappProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);

    // Simulate POST request with unapproved email
    request()->merge(['email' => 'unapproved@example.com']);
    request()->setMethod('POST');

    $response = $provider->login($routerData);

    expect($response->getTargetUrl())->toContain('login/whatsapp');
    expect(session('authRouterErrorMessage'))->toBe('Email not approved for WhatsApp login');
});

it('fails login if approved_emails config is missing', function () {
    $this->app->config->set('services.whatsapp.approved_emails', null);
    $provider = new WhatsappProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);

    request()->merge(['email' => 'test@example.com']);
    request()->setMethod('POST');

    $response = $provider->login($routerData);

    expect($response->getTargetUrl())->toContain('login/whatsapp');
    expect(session('authRouterErrorMessage'))->toBe('WhatsApp login is not configured with approved emails');
});

it('shows waiting page if email is approved', function () {
    $provider = new WhatsappProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);

    // Simulate POST request with approved email
    request()->merge(['email' => 'test@example.com']);
    request()->setMethod('POST');

    $response = $provider->login($routerData);

    expect($response->name())->toBe('auth-router::provider.whatsapp-waiting');
    expect($response->getData()['email'])->toBe('test@example.com');
});

it('authenticates user in callback', function () {
    $provider = new WhatsappProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);

    request()->merge(['email' => 'test@example.com']);

    $response = $provider->callback($routerData);

    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->email)->toBe('test@example.com');
    expect($response->getTargetUrl())->toBe('http://localhost/dashboard');
});

it('fails callback if email is missing', function () {
    $provider = new WhatsappProvider;
    $routerData = new RouterData('dashboard', 'error', 'home', true);

    request()->merge(['email' => '']);

    $response = $provider->callback($routerData);

    expect($response->getTargetUrl())->toBe('http://localhost/error');
    expect(session('authRouterErrorMessage'))->toBe('Email missing in WhatsApp callback');
});
