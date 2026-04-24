<?php

use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\LoginProviders\GoogleProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\UnknownBaseProvider;
use SchenkeIo\LaravelAuthRouter\LoginProviders\WhatsappProvider;

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
    expect($collection[0])->toBeInstanceOf(WhatsappProvider::class)
        ->and($collection[1])->toBeInstanceOf(GoogleProvider::class);
});

it('handles exclusivity of Logto', function () {
    $this->app->config->set('services.logto.endpoint', 'x');
    $this->app->config->set('services.logto.app_id', 'x');
    $this->app->config->set('services.logto.app_secret', 'x');

    $collection = ProviderCollection::fromTextArray(['logto', 'google']);
    expect($collection[1]->valid())->toBeFalse()
        ->and($collection[1]->errors()[0])->toContain('Logto');
});

it('handles exclusivity of WorkOS', function () {
    $this->app->config->set('services.workos.client_id', 'x');
    $this->app->config->set('services.workos.api_key', 'x');
    $this->app->config->set('services.workos.client_secret', 'x');

    $collection = ProviderCollection::fromTextArray(['workos', 'google']);
    expect($collection[1]->valid())->toBeFalse()
        ->and($collection[1]->errors()[0])->toContain('Workos');
});

it('handles exclusivity of Auth0', function () {
    $this->app->config->set('services.auth0.client_id', 'x');
    $this->app->config->set('services.auth0.client_secret', 'x');
    $this->app->config->set('services.auth0.domain', 'x');
    $this->app->config->set('services.auth0.cookie_secret', 'x');

    $collection = ProviderCollection::fromTextArray(['auth0', 'google']);
    expect($collection[1]->valid())->toBeFalse()
        ->and($collection[1]->errors()[0])->toContain('Auth0');
});

it('handles multiple exclusive providers', function () {
    $this->app->config->set('services.logto.endpoint', 'x');
    $this->app->config->set('services.logto.app_id', 'x');
    $this->app->config->set('services.logto.app_secret', 'x');

    $this->app->config->set('services.auth0.client_id', 'x');
    $this->app->config->set('services.auth0.client_secret', 'x');
    $this->app->config->set('services.auth0.domain', 'x');
    $this->app->config->set('services.auth0.cookie_secret', 'x');

    $collection = ProviderCollection::fromTextArray(['logto', 'auth0']);
    expect($collection[1]->valid())->toBeFalse()
        ->and($collection[1]->errors()[0])->toContain('Logto');
});
