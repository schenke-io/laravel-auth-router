<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use SchenkeIo\LaravelAuthRouter\Auth\Service;
use Workbench\App\Services\FakeSocialiteDriver;
use Workbench\App\Services\SignInService;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $signInService = new SignInService(
            all: ['google', 'facebook', 'amazon'],
            workOs: [],
            social: ['google', 'facebook', 'amazon', 'microsoft', 'paypal'],
            drivers: ['google', 'facebook', 'amazon', 'linkedin', 'microsoft', 'paypal', 'auth0', 'stripe', 'whatsapp']
        );

        $this->app->singleton(SignInService::class, fn () => $signInService);

        foreach ($signInService->getDrivers() as $driverName) {
            $service = Service::get($driverName);
            if ($service) {
                $provider = $service->provider();
                $envKeys = $provider->env();
                $presentCount = 0;
                $configData = [];
                foreach ($envKeys as $configKey => $envKey) {
                    $envValue = env($envKey);
                    if ($envValue) {
                        $presentCount++;
                        $configData[$configKey] = $envValue;
                    }
                }

                if ($presentCount === 0) {
                    // All missing -> use fake
                    $this->app['config']->set("services.$driverName", [
                        'client_id' => 'fake',
                        'client_secret' => 'fake',
                        'api_key' => 'fake',
                        'organization_id' => 'fake',
                        'redirect' => 'fake',
                    ]);
                } elseif ($presentCount === count($envKeys)) {
                    // All present -> use real
                    $this->app['config']->set("services.$driverName", $configData);
                } else {
                    // Incomplete -> report error
                    throw new \Exception("Incomplete environment variables for driver: $driverName. Missing some of: ".implode(', ', $envKeys));
                }
            }
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(SignInService $signInService): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'workbench');

        foreach ($signInService->getDrivers() as $driver) {
            Socialite::extend($driver, function ($app) use ($driver) {
                $config = $app['config']['services.'.$driver];

                if (($config['client_id'] ?? '') === 'fake') {
                    return Socialite::buildProvider(FakeSocialiteDriver::class, $config)->setDriverName($driver);
                }

                return null;
            });
        }
    }
}
