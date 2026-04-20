<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\Helpers\ConfigRetriever;
use SocialiteProviders\Manager\SocialiteWasCalled;

it('normalizes string config to array for microsoft', function () {
    Config::set('services.microsoft', 'dummy-client-id');

    // Trigger the event
    Event::dispatch(new SocialiteWasCalled(app(), new ConfigRetriever));

    $config = Config::get('services.microsoft');
    expect($config)->toBeArray()
        ->and($config)->toHaveKey('client_id', 'dummy-client-id');

    // Verify Socialite can resolve it (mocking may be needed if it tries to hit network,
    // but the goal is to check for TypeError during driver creation)
    try {
        Socialite::driver('microsoft');
    } catch (TypeError $e) {
        $this->fail('TypeError encountered: '.$e->getMessage());
    } catch (Exception $e) {
        // Other exceptions are fine, we just want to avoid TypeError
    }
});
