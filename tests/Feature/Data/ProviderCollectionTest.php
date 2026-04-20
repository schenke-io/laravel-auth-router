<?php

use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\LoginProviders\GoogleProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\UnknownBaseProvider;

it('creates from string names', function () {
    $collection = ProviderCollection::fromTextArray(['google']);
    expect($collection->first())->toBeInstanceOf(GoogleProvider::class);
});

it('creates from class names', function () {
    $collection = ProviderCollection::fromTextArray([GoogleProvider::class]);
    expect($collection->first())->toBeInstanceOf(GoogleProvider::class);
});

it('creates from instances', function () {
    $google = new GoogleProvider;
    $collection = ProviderCollection::fromTextArray([$google]);
    expect($collection->first())->toBe($google);
});

it('handles mixed types', function () {
    $google = new GoogleProvider;
    $collection = ProviderCollection::fromTextArray([
        'google',
        GoogleProvider::class,
        $google,
    ]);
    expect($collection)->toHaveCount(3)
        ->and($collection[0])->toBeInstanceOf(GoogleProvider::class)
        ->and($collection[1])->toBeInstanceOf(GoogleProvider::class)
        ->and($collection[2])->toBe($google);
});

it('handles unknown names', function () {
    $collection = ProviderCollection::fromTextArray(['unknown_xyz']);
    expect($collection->first())->toBeInstanceOf(UnknownBaseProvider::class)
        ->and($collection->first()->errors())->not->toBeEmpty();
});

it('sorts email-based providers first', function () {
    $collection = ProviderCollection::fromTextArray(['google', 'whatsapp']);
    expect($collection[0])->toBeInstanceOf(\SchenkeIo\LaravelAuthRouter\LoginProviders\WhatsappProvider::class)
        ->and($collection[1])->toBeInstanceOf(\SchenkeIo\LaravelAuthRouter\LoginProviders\GoogleProvider::class);
});
