<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\Helpers\ConfigRetriever;
use SocialiteProviders\Manager\SocialiteWasCalled;

it('normalizes string config to array for google', function () {
    Config::set('services.google', 'dummy-client-id');

    // Trigger the event (though google doesn't use it, we test our listener)
    Event::dispatch(new SocialiteWasCalled(app(), new ConfigRetriever));

    $config = Config::get('services.google');
    expect($config)->toBeArray()
        ->and($config)->toHaveKey('client_id', 'dummy-client-id');

    // Verify Socialite can resolve it
    try {
        Socialite::driver('google');
    } catch (TypeError $e) {
        $this->fail('TypeError encountered: '.$e->getMessage());
    } catch (Exception $e) {
        // Other exceptions are fine, we just want to avoid TypeError
    }
});
