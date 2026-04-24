<?php

use SchenkeIo\LaravelAuthRouter\Auth\Service;
use SchenkeIo\LaravelAuthRouter\Contracts\UseExclusiveInterface;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;

it('prohibits any mix of exclusive and other social providers', function () {
    $exclusiveProviders = [];
    $otherSocialProviders = [];

    foreach (Service::cases() as $service) {
        $provider = $service->provider();
        if ($provider instanceof UseExclusiveInterface) {
            $exclusiveProviders[] = $provider;
        } elseif ($provider->isSocial()) {
            $otherSocialProviders[] = $provider;
        }
    }

    foreach ($exclusiveProviders as $exclusive) {
        foreach ($otherSocialProviders as $other) {
            $collection = ProviderCollection::fromTextArray([$exclusive, $other]);

            // the second one should have an error
            expect($collection[1]->valid())->toBeFalse();

            $found = false;
            $expectedError = ucfirst($exclusive->name);
            foreach ($collection[1]->errors() as $error) {
                if (str_contains($error, $expectedError)) {
                    $found = true;
                    break;
                }
            }
            expect($found)->toBeTrue("Expected error containing '$expectedError' not found in: ".implode(', ', $collection[1]->errors()));
        }
    }
});

it('allows mixing exclusive providers with non-social providers', function () {
    $exclusiveProviders = [];
    $nonSocialProviders = [];

    foreach (Service::cases() as $service) {
        $provider = $service->provider();
        if ($provider instanceof UseExclusiveInterface) {
            $exclusiveProviders[] = $provider;
        } elseif (! $provider->isSocial()) {
            $nonSocialProviders[] = $provider;
        }
    }

    foreach ($exclusiveProviders as $exclusive) {
        foreach ($nonSocialProviders as $nonSocial) {
            $collection = ProviderCollection::fromTextArray([$exclusive, $nonSocial]);

            // none of them should have an exclusivity error
            $expectedError = ucfirst($exclusive->name);
            foreach ($collection as $p) {
                foreach ($p->errors() as $error) {
                    expect($error)->not->toContain($expectedError);
                }
            }
        }
    }
});

it('prohibits mixing two exclusive providers', function () {
    $exclusiveProviders = [];

    foreach (Service::cases() as $service) {
        $provider = $service->provider();
        if ($provider instanceof UseExclusiveInterface) {
            $exclusiveProviders[] = $provider;
        }
    }

    if (count($exclusiveProviders) < 2) {
        return;
    }

    for ($i = 0; $i < count($exclusiveProviders); $i++) {
        for ($j = 0; $j < count($exclusiveProviders); $j++) {
            if ($i === $j) {
                continue;
            }

            $exclusive1 = $exclusiveProviders[$i];
            $exclusive2 = $exclusiveProviders[$j];

            $collection = ProviderCollection::fromTextArray([$exclusive1, $exclusive2]);

            // one of them must be invalid
            expect($collection->contains(fn ($p) => ! $p->valid()))->toBeTrue();
        }
    }
});
