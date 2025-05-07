<?php

use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Workbench\App\Models\User;

it('can partly update a user', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $name = 'test name';
    $name2 = 'updated name';
    $email = 'test@example.com';
    $avatar = 'http://example.com/avatar.jpg';
    $avatar2 = 'http://example.com/avatar2.jpg';

    $userData = new UserData($name2, $email, $avatar2);
    $routerData = getRouterData(false);

    $user = User::factory()->create(['name' => $name, 'email' => $email, 'avatar' => $avatar]);
    $response = $userData->authAndRedirect($routerData);
    $this->assertEquals('http://localhost/route-success', $response->getTargetUrl());
    $user2 = $user->fresh();
    $this->assertEquals($user->id, $user2->id);
    $this->assertEquals($name, $user2->name);
    $this->assertEquals($avatar2, $user2->avatar);
});

it('rejects new users when not allowed', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $name = 'test name';
    $name2 = 'updated name';
    $email = 'test@example.com';
    $avatar = 'http://example.com/avatar.jpg';
    $avatar2 = 'http://example.com/avatar2.jpg';

    $userData = new UserData($name2, $email, $avatar2);
    $routerData = getRouterData(false);
    $this->assertEquals(0, User::count());
    $response = $userData->authAndRedirect($routerData);
    $this->assertEquals('UnableToAddNewUsers', customErrorType($response));
    $this->assertEquals('http://localhost/route-error', $response->getTargetUrl());
    $this->assertEquals(0, User::count());
});

it('can add new user when allowed', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $name = 'test name';
    $email = 'test@example.com';
    $avatar = 'http://example.com/avatar.jpg';

    $userData = new UserData($name, $email, $avatar);
    $routerData = getRouterData(true);
    $this->assertEquals(0, User::count());
    $response = $userData->authAndRedirect($routerData);
    $this->assertEquals(1, User::count());
});

it('handles missing email', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $name = 'test name';
    $email = 'test@example.com';
    $avatar = 'http://example.com/avatar.jpg';

    $userData = new UserData($name, '', $avatar);
    $routerData = getRouterData(true);

    $this->assertEquals(0, User::count());
    $response = $userData->authAndRedirect($routerData);
    $this->assertEquals('EmailMissing', customErrorType($response));
    $this->assertEquals(0, User::count());
});

it('handles invalid email', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $name = 'test name';
    $email = 'test@example.com';
    $avatar = 'http://example.com/avatar.jpg';

    $userData = new UserData($name, 'invalid email @', $avatar);
    $routerData = getRouterData(true);

    $this->assertEquals(0, User::count());
    $response = $userData->authAndRedirect($routerData);
    $this->assertEquals('InvalidEmail', customErrorType($response));
    $this->assertEquals(0, User::count());
});
