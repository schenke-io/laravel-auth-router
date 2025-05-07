<?php

use SchenkeIo\LaravelAuthRouter\Data\RouterData;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function routeNames(): array
{
    $return = [];
    foreach (Route::getRoutes() as $route) {
        $return[] = $route->getName();
    }

    return $return;
}

function customErrorType(Response $response): ?string
{
    $headers = $response->headers->all();

    return $headers['x-custom-error-type'][0] ?? null;
}

function getRouterData(bool $canAddNewUser): RouterData
{
    Route::get('route-success', fn () => 'route-success')->name('route-success');
    Route::get('route-error', fn () => 'route-error')->name('route-error');
    Route::get('route-home', fn () => 'route-home')->name('route-home');

    return new RouterData('route-success', 'route-error', 'route-home', $canAddNewUser);
}
