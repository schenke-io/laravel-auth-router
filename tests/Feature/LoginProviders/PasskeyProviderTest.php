<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\LoginProviders;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Mockery;
use SchenkeIo\LaravelAuthRouter\Contracts\PasskeyMailerInterface;
use Workbench\App\Models\User;

beforeEach(function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    // needed for route registration
    $this->app->config->set('services.passkey', []);

    Route::get('/', fn () => 'home')->name('home');
    Route::get('/dashboard', fn () => 'dashboard')->name('dashboard');
    Route::get('/error', fn () => 'error')->name('error');

    // Register passkey routes
    Route::authRouter(['passkey'])->success('dashboard')->error('error')->home('home');

    app('router')->getRoutes()->refreshNameLookups();
});

it('renders the initial login page', function () {
    $response = $this->get('/login/passkey');
    $response->assertStatus(200);
    $response->assertViewIs('auth-router::login');
    $response->assertViewHas('providers');
});

it('sends an OTP and shows the OTP view', function () {
    $mailer = Mockery::mock(PasskeyMailerInterface::class);
    $mailer->shouldReceive('sendMail')->once()->with(Mockery::type('string'));
    $this->app->instance(PasskeyMailerInterface::class, $mailer);

    $this->post('/login/passkey', ['email' => 'test@example.com'])
        ->assertStatus(200)
        ->assertViewIs('auth-router::login')
        ->assertViewHas('showOtp', true)
        ->assertViewHas('email', 'test@example.com');

    $this->assertEquals('test@example.com', session('passkey_email'));
    $this->assertNotNull(session('passkey_otp'));
});

it('handles invalid email in login', function () {
    $this->post('/login/passkey', ['email' => 'invalid-email'])
        ->assertRedirect('http://localhost/login/passkey')
        ->assertSessionHas('authRouterErrorMessage', 'Valid email is required');
});

it('handles invalid OTP in login', function () {
    session(['passkey_email' => 'test@example.com', 'passkey_otp' => '123456']);

    $this->post('/login/passkey', [
        'email' => 'test@example.com',
        'otp' => 'wrong-otp',
    ])
        ->assertRedirect('http://localhost/login/passkey')
        ->assertSessionHas('authRouterErrorMessage', 'Invalid OTP');
});

it('verifies OTP and shows initiation view', function () {
    session(['passkey_email' => 'test@example.com', 'passkey_otp' => '123456']);

    $this->post('/login/passkey', [
        'email' => 'test@example.com',
        'otp' => '123456',
    ])
        ->assertStatus(200)
        ->assertViewIs('auth-router::login')
        ->assertViewHas('initiatePasskey', true);
});

it('handles callback and authenticates user', function () {
    session(['passkey_email' => 'test@example.com']);

    // Call callback route
    $response = $this->get('/callback/passkey');

    $this->assertTrue(Auth::check());
    $this->assertEquals('test@example.com', Auth::user()->email);
    $this->assertEquals('http://localhost/dashboard', $response->getTargetUrl());
});

it('fails callback if session expired', function () {
    session()->forget('passkey_email');

    $response = $this->get('/callback/passkey');
    $response->assertRedirect('http://localhost/login/passkey');
    $this->assertEquals('Session expired', session('authRouterErrorMessage'));
});
