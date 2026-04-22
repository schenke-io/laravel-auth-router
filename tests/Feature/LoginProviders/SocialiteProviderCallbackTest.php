<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\LoginProviders\AmazonProvider;

it('returns error redirect when callback has denied or error in request', function (string $key) {
    request()->merge([$key => 'some error']);

    $provider = new AmazonProvider;
    $routerData = new RouterData('dashboard', 'error-test', 'home', true);

    Redirect::shouldReceive('route')
        ->with('error-test')
        ->andReturn(Mockery::mock(RedirectResponse::class, function ($mock) {
            $mock->shouldReceive('withInput')->andReturnSelf();
            $mock->shouldReceive('with')->andReturnSelf();
            $mock->shouldReceive('withHeaders')->andReturnSelf();
            $mock->shouldReceive('getTargetUrl')->andReturn('/error-test');
        }));

    $response = $provider->callback($routerData);

    expect($response->getTargetUrl())->toBe('/error-test');
})->with(['denied', 'error']);
