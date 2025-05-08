<?php

use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Facades\Socialite;
use SchenkeIo\LaravelAuthRouter\AuthRouterServiceProvider;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Manager\Helpers\ConfigRetriever;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft;
use SocialiteProviders\Stripe;
use Spatie\LaravelPackageTools\Package;
use Workbench\App\Models\User;

beforeEach(function () {
    Route::clearResolvedInstances();
});

it('configures the package correctly', function () {
    User::factory()->create();
    // Create a mock Package object
    $package = Mockery::mock(Package::class);

    // Expect the methods to be called on the package mock
    $package->shouldReceive('name')
        ->once()
        ->with('auth-router')
        ->andReturn($package); // Allow chaining

    $package->shouldReceive('hasTranslations')
        ->once()
        ->andReturn($package); // Allow chaining

    $package->shouldReceive('hasViews')
        ->once()
        ->with('auth-router')
        ->andReturn($package); // Allow chaining

    // Create an instance of your service provider
    $serviceProvider = new AuthRouterServiceProvider($this->app);

    // Call the configurePackage method
    $serviceProvider->configurePackage($package);
});

it('defines a single provider', function ($providerInput) {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');

    $serviceProvider = new AuthRouterServiceProvider($this->app);
    $serviceProvider->boot();
    Route::authRouter($providerInput, 'success', 'error');
    $routeNames = routeNames();
    $this->assertTrue(in_array('login.google', $routeNames));
    $this->assertTrue(in_array('callback.google', $routeNames));
    $this->assertTrue(in_array('login', $routeNames));
    $this->assertTrue(in_array('logout', $routeNames));
})->with(['google', ['google']]);

it('handles a single defect providers', function ($providerInput) {

    // no services configured
    $serviceProvider = new AuthRouterServiceProvider($this->app);
    $serviceProvider->boot();
    Route::authRouter($providerInput, 'success', 'error');
    $routeNames = routeNames();
    $this->assertFalse(in_array('login.google', $routeNames));
    $this->assertFalse(in_array('callback.google', $routeNames));
    $this->assertTrue(in_array('login', $routeNames));
    $this->assertTrue(in_array('logout', $routeNames));
})->with(['', 'something wrong', 'google', ['google']]);

it('defines some providers', function () {
    $this->app->config->set('services.google.client_id', 'google_client_id');
    $this->app->config->set('services.google.client_secret', 'google_client_secret');
    $this->app->config->set('services.microsoft.client_id', 'microsoft_client_id');
    $this->app->config->set('services.microsoft.client_secret', 'microsoft_client_secret');

    $serviceProvider = new AuthRouterServiceProvider($this->app);
    $serviceProvider->boot();
    Route::authRouter(['google', 'microsoft'], 'success', 'error');
    $this->assertEquals(7, Route::getRoutes()->count());
});

it('register providers', function () {
    $serviceProvider = new AuthRouterServiceProvider($this->app);
    $serviceProvider->boot();

    // Let's provide mocks for these if we new it up directly.
    $socialiteFactoryMock = $this->app->make(Factory::class);
    $configRetrieverMock = Mockery::mock(ConfigRetriever::class);
    // Allow any necessary calls on configRetriever if extendSocialite's internal closure uses it.
    $configRetrieverMock->shouldReceive('fromServices')->times(2)->andReturn(
        new Config(
            'client_id',
            'client_secret',
            'redirect'
        )
    );

    Event::dispatch(new SocialiteWasCalled($this->app, $configRetrieverMock));
    $microsoftDriver = Socialite::driver('microsoft');
    $this->assertInstanceOf(Microsoft\Provider::class, $microsoftDriver);

    $stripeDriver = Socialite::driver('stripe');
    $this->assertInstanceOf(Stripe\Provider::class, $stripeDriver);

});
