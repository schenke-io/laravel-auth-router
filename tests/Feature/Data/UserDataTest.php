<?php

use Illuminate\Foundation\Auth\User as Authenticatable;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Workbench\App\Models\User;

class TestUserWithoutInterface extends Authenticatable
{
    protected $table = 'users';

    protected $fillable = ['name', 'email', 'avatar'];
}

it('can partly update a user', function () {
    $this->app->config->set('auth.providers.users.model', TestUserWithoutInterface::class);
    $name = 'test name';
    $name2 = 'updated name';
    $email = 'test@example.com';
    $avatar = 'https://example.com/avatar.jpg';
    $avatar2 = 'https://example.com/avatar2.jpg';

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
    $avatar = 'https://example.com/avatar.jpg';
    $avatar2 = 'https://example.com/avatar2.jpg';

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
    $avatar = 'https://example.com/avatar.jpg';

    $userData = new UserData($name, $email, $avatar);
    $routerData = getRouterData(true);
    $this->assertEquals(0, User::count());
    $response = $userData->authAndRedirect($routerData);
    $this->assertEquals(1, User::count());
    $newUser = User::first();
    expect($newUser->avatar)->toBe($avatar);
});

it('can add new user when allowed for models without interface', function () {
    $this->app->config->set('auth.providers.users.model', TestUserWithoutInterface::class);
    $name = 'test name';
    $email = 'test@example.com';
    $avatar = 'https://example.com/avatar.jpg';

    $userData = new UserData($name, $email, $avatar);
    $routerData = getRouterData(true);
    $this->assertEquals(0, TestUserWithoutInterface::count());
    $response = $userData->authAndRedirect($routerData);
    $this->assertEquals(1, TestUserWithoutInterface::count());
    $newUser = TestUserWithoutInterface::first();
    expect($newUser->avatar)->toBe($avatar);
    expect($newUser->name)->toBe($name);
    expect($newUser->email)->toBe($email);
});

it('can add new user without avatar for models without interface', function () {
    $this->app->config->set('auth.providers.users.model', TestUserWithoutInterface::class);
    $name = 'test name';
    $email = 'test@example.com';

    $userData = new UserData($name, $email, null);
    $routerData = getRouterData(true);
    $this->assertEquals(0, TestUserWithoutInterface::count());
    $response = $userData->authAndRedirect($routerData);
    $this->assertEquals(1, TestUserWithoutInterface::count());
    $newUser = TestUserWithoutInterface::first();
    expect($newUser->avatar)->toBeNull();
});

it('handles missing email', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $name = 'test name';
    $email = 'test@example.com';
    $avatar = 'https://example.com/avatar.jpg';

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
    $avatar = 'https://example.com/avatar.jpg';

    $userData = new UserData($name, 'invalid email @', $avatar);
    $routerData = getRouterData(true);

    $this->assertEquals(0, User::count());
    $response = $userData->authAndRedirect($routerData);
    $this->assertEquals('InvalidEmail', customErrorType($response));
    $this->assertEquals(0, User::count());
});

/*
 * checks line 89 in src/Data/UserData.php
 */
it('prefers an intended url over the given success url', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $name = 'test name';
    $email = 'test@example.com';
    $avatar = 'https://example.com/avatar.jpg';

    // Create a user
    $user = User::factory()->create(['name' => $name, 'email' => $email, 'avatar' => $avatar]);

    // Set up UserData and RouterData
    $userData = new UserData($name, $email, $avatar);
    $routerData = getRouterData(true);

    // Set an intended URL in the session
    $intendedUrl = 'http://localhost/intended-url';
    session()->put('url.intended', $intendedUrl);

    // Call authAndRedirect
    $response = $userData->authAndRedirect($routerData);

    // Verify that the response redirects to the intended URL, not the success URL
    $this->assertEquals($intendedUrl, $response->getTargetUrl());
});

it('can handle optional avatar', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $name = 'test name';
    $email = 'test-optional@example.com';

    $userData = new UserData($name, $email, null);
    $routerData = getRouterData(true);

    $response = $userData->authAndRedirect($routerData);
    $user = User::where('email', $email)->first();
    $this->assertNotNull($user);
    $this->assertEquals($name, $user->name);
    $this->assertEmpty($user->avatar);
});

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;
use SchenkeIo\LaravelAuthRouter\Traits\InteractsWithAuthRouter;

class TestUserWithInterface extends User implements AuthenticatableRouterUser
{
    use InteractsWithAuthRouter;

    protected $table = 'users';

    public bool $setNameCalled = false;

    public function setName(string $name): void
    {
        $this->setNameCalled = true;
        $this->name = $name;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function findByEmail(string $email): ?Model
    {
        if (app()->bound(self::class)) {
            return app(self::class)->findByEmail($email);
        }

        return $this->where('email', $email)->first();
    }
}

it('supports AuthenticatableRouterUser interface', function () {
    $userModelClass = TestUserWithInterface::class;
    $this->app->config->set('auth.providers.users.model', $userModelClass);

    $name = 'New Name';
    $email = 'test-interface@example.com';
    $userData = new UserData($name, $email, 'https://example.com/avatar.jpg');
    $routerData = getRouterData(true);

    // 1. Test creation of new user
    $userData->authAndRedirect($routerData);
    $user = $userModelClass::where('email', $email)->first();
    $this->assertNotNull($user);
    $this->assertEquals($name, $user->name);

    // 2. Test update of existing user
    $newName = 'Updated Name';
    $userData2 = new UserData($newName, $email, 'https://example.com/new-avatar.jpg');
    $userData2->authAndRedirect($routerData);
    $user->refresh();
    $this->assertEquals($newName, $user->name);
});

it('can create UserData from Socialite User', function () {
    $socialiteUser = Mockery::mock(Laravel\Socialite\Contracts\User::class);
    $socialiteUser->shouldReceive('getName')->andReturn('Socialite Name');
    $socialiteUser->shouldReceive('getEmail')->andReturn('socialite@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/socialite.jpg');
    $socialiteUser->shouldReceive('getId')->andReturn('12345');

    $userData = UserData::fromUser($socialiteUser, 'google');

    expect($userData->name)->toBe('Socialite Name')
        ->and($userData->email)->toBe('socialite@example.com')
        ->and($userData->provider)->toBe('google')
        ->and($userData->avatar)->toBe('https://example.com/socialite.jpg');
});

it('can create UserData from Auth0 data', function () {
    $data = [
        'name' => 'Auth0 Name',
        'email' => 'auth0@example.com',
        'picture' => 'https://example.com/auth0.jpg',
    ];

    $userData = UserData::fromAuth0($data);

    expect($userData->name)->toBe('Auth0 Name')
        ->and($userData->email)->toBe('auth0@example.com')
        ->and($userData->avatar)->toBe('https://example.com/auth0.jpg');
});

it('accepts https avatar urls', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $avatar = 'https://example.com/avatar.jpg';
    $userData = new UserData('Test', 'test@example.com', $avatar);
    $routerData = getRouterData(true);

    $userData->authAndRedirect($routerData);
    $user = User::where('email', 'test@example.com')->first();
    expect($user->avatar)->toBe($avatar);
});

it('rejects http avatar urls', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $avatar = 'http://example.org/avatar.jpg';
    $userData = new UserData('Test', 'test@example.com', $avatar);
    $routerData = getRouterData(true);

    $userData->authAndRedirect($routerData);
    $user = User::where('email', 'test@example.com')->first();
    expect($user->avatar)->toBeNull();
});

it('rejects data avatar urls', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $avatar = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
    $userData = new UserData('Test', 'test@example.com', $avatar);
    $routerData = getRouterData(true);

    $userData->authAndRedirect($routerData);
    $user = User::where('email', 'test@example.com')->first();
    expect($user->avatar)->toBeNull();
});

it('rejects very large data avatar urls', function () {
    $this->app->config->set('auth.providers.users.model', User::class);
    $avatar = 'data:image/png;base64,'.str_repeat('A', 1000000); // 1MB data URL
    $userData = new UserData('Test', 'test@example.com', $avatar);
    $routerData = getRouterData(true);

    $userData->authAndRedirect($routerData);
    $user = User::where('email', 'test@example.com')->first();
    expect($user->avatar)->toBeNull();
});

it('trait setAvatar rejects and truncates data urls', function () {
    $user = new class
    {
        use InteractsWithAuthRouter;

        public $avatar = 'original';
    };
    $largeDataUrl = 'data:image/png;base64,'.str_repeat('A', 1000);
    $user->setAvatar($largeDataUrl);
    expect($user->avatar)->toBeNull();
});
