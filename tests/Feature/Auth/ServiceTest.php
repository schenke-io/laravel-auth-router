<?php

use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Service;

it('returns the correct provider for each enum case', function () {
    foreach (Service::cases() as $case) {
        $provider = $case->provider();
        expect($provider)->toBeInstanceOf(BaseProvider::class);
    }
});

it('returns the correct service for each provider name', function () {
    foreach (Service::cases() as $case) {
        expect(Service::get($case->name))->toBe($case)
            ->and(Service::get(strtoupper($case->name)))->toBe($case)
            ->and(Service::get(str_replace('_', '', $case->name)))->toBe($case);
    }
});

it('returns null for unknown provider names', function () {
    expect(Service::get('unknown'))->toBeNull()
        ->and(Service::get(''))->toBeNull();
});
