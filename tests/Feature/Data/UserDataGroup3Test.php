<?php

use Illuminate\Support\Facades\Auth;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Workbench\App\Models\User;

it('can find user by providerId', function () {
    $this->app->config->set('auth.providers.users.model', User::class);

    $providerId = 'provider-123';
    $email = 'test@example.com';
    $user = User::factory()->create([
        'email' => $email,
        'provider_id' => $providerId,
    ]);

    $userData = new UserData('Test', $email, '', 'google', $providerId);
    $routerData = getRouterData(false);
    $routerData->useProviderId = true;

    $response = $userData->authAndRedirect($routerData);

    $this->assertEquals('http://localhost/route-success', $response->getTargetUrl());
    $this->assertTrue(Auth::check());
    $this->assertEquals($user->id, Auth::id());
});

it('migrates providerId if found by email', function () {
    $this->app->config->set('auth.providers.users.model', User::class);

    $providerId = 'provider-123';
    $email = 'test-migrate@example.com';
    $user = User::factory()->create([
        'email' => $email,
        'provider_id' => null,
    ]);

    $userData = new UserData('Test', $email, '', 'google', $providerId);
    $routerData = getRouterData(false);
    $routerData->useProviderId = true;

    $response = $userData->authAndRedirect($routerData);

    $this->assertEquals('http://localhost/route-success', $response->getTargetUrl());
    $user->refresh();
    $this->assertEquals($providerId, $user->provider_id);
});

it('rejects login if email matches but providerId is different', function () {
    $this->app->config->set('auth.providers.users.model', User::class);

    $email = 'test-reject@example.com';
    User::factory()->create([
        'email' => $email,
        'provider_id' => 'existing-provider-id',
    ]);

    $userData = new UserData('Test', $email, '', 'google', 'new-provider-id');
    $routerData = getRouterData(false);
    $routerData->useProviderId = true;

    $response = $userData->authAndRedirect($routerData);

    $this->assertEquals('MixedProviders', customErrorType($response));
    $this->assertFalse(Auth::check());
});

it('enforces exclusivity when useProviderId is false', function () {
    $this->app->config->set('auth.providers.users.model', User::class);

    $email = 'test-exclusive@example.com';
    User::factory()->create([
        'email' => $email,
        'provider_id' => 'other-provider-id',
    ]);

    // Exclusive login attempt for a different provider
    $userData = new UserData('Test', $email, '', 'exclusive-provider', 'new-id', true);
    $routerData = getRouterData(false);
    $routerData->useProviderId = false; // Not using providerId for lookup

    $response = $userData->authAndRedirect($routerData);

    $this->assertEquals('ExclusiveProvider', customErrorType($response));
    $this->assertFalse(Auth::check());
});

it('allows exclusive login if providerId matches', function () {
    $this->app->config->set('auth.providers.users.model', User::class);

    $email = 'test-exclusive-ok@example.com';
    $providerId = 'exclusive-id';
    User::factory()->create([
        'email' => $email,
        'provider_id' => $providerId,
    ]);

    $userData = new UserData('Test', $email, '', 'exclusive-provider', $providerId, true);
    $routerData = getRouterData(false);
    $routerData->useProviderId = true;

    $response = $userData->authAndRedirect($routerData);

    $this->assertEquals('http://localhost/route-success', $response->getTargetUrl());
    $this->assertTrue(Auth::check());
});

class TestUserWithoutInterfaceGroup3 extends Illuminate\Foundation\Auth\User
{
    protected $table = 'users';

    protected $fillable = ['name', 'email', 'avatar', 'provider_id'];
}

it('works with models not implementing AuthenticatableRouterUser', function () {
    $this->app->config->set('auth.providers.users.model', TestUserWithoutInterfaceGroup3::class);

    $providerId = 'provider-no-interface';
    $email = 'no-interface@example.com';
    User::factory()->create([
        'email' => $email,
        'provider_id' => $providerId,
    ]);

    $userData = new UserData('Test', $email, '', 'google', $providerId);
    $routerData = getRouterData(false);
    $routerData->useProviderId = true;

    $response = $userData->authAndRedirect($routerData);

    $this->assertEquals('http://localhost/route-success', $response->getTargetUrl());
    $this->assertTrue(Auth::check());
    $loggedInUser = Auth::user();
    $this->assertEquals($email, $loggedInUser->email);
});
