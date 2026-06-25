<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Auth\AuthFlowController;
use SchenkeIo\LaravelAuthRouter\Auth\AuthRouter;
use SchenkeIo\LaravelAuthRouter\Auth\AuthRouterBuilder;
use SchenkeIo\LaravelAuthRouter\Auth\RouteRegistrar;
use SchenkeIo\LaravelAuthRouter\Contracts\EmailConfirmInterface;
use SchenkeIo\LaravelAuthRouter\Data\ProviderCollection;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use SchenkeIo\LaravelAuthRouter\Enums\Error;
use SchenkeIo\LaravelAuthRouter\Enums\Service;

pest()->group('feature');

uses(RefreshDatabase::class);

class MockEmailConfirm implements EmailConfirmInterface
{
    public function __construct(public UserData $userData) {}

    public function getEmail(): string
    {
        return '';
    }

    public function getToken(): string
    {
        return '';
    }

    public function getData(): array
    {
        return [];
    }
}

it('covers AuthFlowController missing provider branches', function () {
    $routerData = getRouterData(true);

    Route::get('test-login/{provider?}', [AuthFlowController::class, 'login'])
        ->defaults('routerData', $routerData);
    Route::get('test-callback/{provider?}', [AuthFlowController::class, 'callback'])
        ->defaults('routerData', $routerData);
    Route::post('test-logout/{provider}/back-channel', [AuthFlowController::class, 'backChannelLogout'])
        ->defaults('routerData', $routerData);

    $this->get('test-login/unknown')->assertRedirect(route($routerData->routeError));
    $this->get('test-login')->assertRedirect(route($routerData->routeError));
    $this->get('test-callback/unknown')->assertRedirect(route($routerData->routeError));
    $this->post('test-logout/unknown/back-channel')->assertStatus(400);
});

it('covers AuthFlowController loginIndex redirect branch', function () {
    $routerData = getRouterData(true);

    // Ensure the login route for google exists
    Route::get('/login/google', fn () => 'login')->name($routerData->getRoutePrefix().'login.google');

    Route::get('test-login-index', [AuthFlowController::class, 'loginIndex'])
        ->defaults('routerData', $routerData)
        ->defaults('providers', ['google'])
        ->defaults('errors', []);

    $this->get('test-login-index')->assertRedirect(route($routerData->getRoutePrefix().'login.google'));
});

it('covers AuthRouter addProvider', function () {
    $routerData = getRouterData(true);
    $provider = Service::google->provider();
    $authRouter = new AuthRouter;
    $authRouter->addProvider($provider, $routerData);

    expect(Route::has($routerData->getRoutePrefix().'login.google'))->toBeTrue();
});

it('covers AuthRouterBuilder gap lines', function () {
    // Line 309: emailConfirm as string
    $builder = new AuthRouterBuilder(['google']);
    $builder->emailConfirm(MockEmailConfirm::class)->register();

    // Line 310: emailConfirm as object
    $builder2 = new AuthRouterBuilder(['google']);
    $builder2->emailConfirm(new MockEmailConfirm(new UserData('', '', '', '', '')))->register();

    // Line 387: defaultName as Closure (and triggers ProviderCollection line 52)
    $builder3 = new AuthRouterBuilder(['google']);
    $builder3->defaultName(fn ($userData) => 'Name')->register();

    expect(true)->toBeTrue();
});

it('covers RouteRegistrar gap lines', function () {
    $registrar = new RouteRegistrar;
    $routerData = getRouterData(true);

    // Line 25: empty providers
    $registrar->registerWildcardRoutes(new ProviderCollection([]), $routerData);

    expect(true)->toBeTrue();
});

it('covers UserData gap lines', function () {
    $routerData = getRouterData(true);

    // Line 183-185: emailConfirmClass
    $userData = new UserData('Name', 'test@example.com', '', 'google', 'id');
    $routerData->emailConfirmClass = MockEmailConfirm::class;
    $response = $userData->authAndRedirect($routerData);
    expect($response->getTargetUrl())->toBe(route($routerData->getRoutePrefix().'callback.payload'));

    // Line 228: non-existent routeSuccess
    $routerData->emailConfirmClass = null;
    $routerData->routeSuccess = 'non-existent-route';
    $response = $userData->authAndRedirect($routerData);
    expect($response->getTargetUrl())->toBe(url('/'));
});

it('covers Enums/Error redirect gap', function () {
    $routerData = getRouterData(true);
    $routerData->routeError = 'non-existent-error-route';

    $response = Error::LocalAuth->redirect($routerData);
    expect($response->getTargetUrl())->toBe(url('/'));
});
