<?php

use SchenkeIo\LaravelAuthRouter\LoginProviders\UnknownBaseProvider;

it('has no env data', function () {
    $this->assertCount(0, (new UnknownBaseProvider)->env());

});

it('can handle a dummy login', function () {
    $routerData = getRouterData(true);
    $this->assertEquals('http://localhost', (new UnknownBaseProvider)->login($routerData)->getTargetUrl());
});

it('can handle a dummy callback', function () {
    $routerData = getRouterData(true);
    $this->assertEquals('http://localhost', (new UnknownBaseProvider)->callback($routerData)->getTargetUrl());
});
