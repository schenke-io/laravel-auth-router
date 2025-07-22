<?php

namespace SchenkeIo\LaravelAuthRouter;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use SchenkeIo\LaravelAuthRouter\Auth\AuthRouter;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft;
use SocialiteProviders\Stripe;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuthRouterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('auth-router')->hasTranslations()->hasViews('auth-router');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Router::macro('authRouter', function (
            string|array $providerKeys,
            string $routeSuccess,
            string $routeError,
            string $routeHome = 'home',
            bool $canAddUsers = true
        ) {

            $providers = ProviderCollection::fromTextArray($providerKeys);
            $routerData = new RouterData($routeSuccess, $routeError, $routeHome, $canAddUsers);
            $authRouter = new AuthRouter;
            // add the routes for any provider
            $authRouter->addProviders($providers, $routerData);
            // add the login selector or redirect
            $authRouter->addLogin($providers, $routerData);
            // add the central logout
            $authRouter->addLogout($routeHome);
        });
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', Microsoft\Provider::class);
            $event->extendSocialite('stripe', Stripe\Provider::class);
        });

        // Register views for package consumers
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'auth-router');
        // Register lang files for package consumers
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'auth-router');

    }

    /**
     * Register any application services.
     */
    public function register(): void {}
}
