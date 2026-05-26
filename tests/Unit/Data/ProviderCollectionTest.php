<?php

pest()->group('unit');

use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Contracts\UseExclusiveInterface;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;

abstract class MockExclusiveProvider extends BaseProvider implements UseExclusiveInterface {}

it('sorts email-based providers first', function () {
    $social = mock(BaseProvider::class);
    $social->shouldReceive('isSocial')->andReturn(true);

    $nonSocial = mock(BaseProvider::class);
    $nonSocial->shouldReceive('isSocial')->andReturn(false);

    $collection = new ProviderCollection([$social, $nonSocial]);
    $sorted = $collection->sortProviders();

    expect($sorted[0])->toBe($nonSocial)
        ->and($sorted[1])->toBe($social);
});

it('handles exclusivity of special providers', function () {
    $exclusive = mock(MockExclusiveProvider::class);
    $exclusive->name = 'exclusive';
    $exclusive->shouldReceive('isSocial')->andReturn(true);

    $social = mock(BaseProvider::class);
    $social->shouldReceive('isSocial')->andReturn(true);
    $social->shouldReceive('addError')->once();

    $collection = new ProviderCollection([$exclusive, $social]);
    $collection->handleExclusivity();
});

it('can be restored via __set_state', function () {
    $items = [mock(BaseProvider::class)->makePartial()];
    $collection = ProviderCollection::__set_state(['items' => $items]);

    expect($collection)->toBeInstanceOf(ProviderCollection::class)
        ->and($collection->all())->toBe($items);
});
