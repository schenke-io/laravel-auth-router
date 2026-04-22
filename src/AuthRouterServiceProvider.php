<?php

namespace SchenkeIo\LaravelAuthRouter;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use SchenkeIo\LaravelAuthRouter\Auth\AuthRouterBuilder;
use SchenkeIo\LaravelAuthRouter\Services\AppleTokenGenerator;
use SocialiteProviders\Apple;
use SocialiteProviders\Manager\Helpers\ConfigRetriever;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft;
use SocialiteProviders\Stripe;
use Spatie\LaravelPackageTools\Exceptions\InvalidPackage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for registering authentication routes and macros.
 */
class AuthRouterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('auth-router')
            ->hasTranslations()
            ->hasViews('auth-router');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Router::macro('authRouter', function (string|array $providerKeys) {
            return new AuthRouterBuilder($providerKeys);
        });

        Event::listen(function (SocialiteWasCalled $event) {
            /*
             * Ensure configuration is an array for supported drivers
             * to prevent TypeErrors in Socialite 5.14+ / PHP 8.0+
             */
            foreach (['microsoft', 'stripe', 'apple', 'google'] as $name) {
                $key = "services.$name";
                $config = config($key);
                if (is_string($config)) {
                    config([$key => [
                        'client_id' => $config,
                        'client_secret' => '',
                        'redirect' => '',
                    ]]);
                }
            }
            $socialite = app(SocialiteFactory::class);
            foreach ([
                'microsoft' => Microsoft\Provider::class,
                'stripe' => Stripe\Provider::class,
                'apple' => Apple\Provider::class,
            ] as $name => $class) {
                $socialite->extend($name, function () use ($socialite, $name, $class) {
                    $config = (new ConfigRetriever)->fromServices($name);

                    return $socialite->buildProvider($class, $config->get());
                });
            }
        });

        // Register views for package consumers
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'auth-router');
        // Register lang files for package consumers
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'auth-router');

    }

    /**
     * Register any application services.
     *
     * @throws InvalidPackage
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(AppleTokenGenerator::class);
    }
}
