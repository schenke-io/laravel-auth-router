<?php

use Illuminate\Support\Facades\Auth;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use Workbench\App\Models\User;

beforeEach(function () {
    $this->app->config->set('auth.providers.users.model', User::class);
});

it('follows the lifecycle from email-only to provider_id-aware', function () {
    $email = 'lifecycle@example.com';
    $providerId = 'id-123';
    $provider = 'google';

    // 1. Initial state: User exists with email but no provider_id
    $user = User::factory()->create([
        'email' => $email,
        'provider_id' => null,
    ]);

    // 2. Login with useProviderId = false (backward compatibility)
    $userData = new UserData('User One', $email, '', $provider, $providerId, false);
    $routerData = getRouterData(false);
    $routerData->useProviderId = false;

    $userData->authAndRedirect($routerData);

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);

    $user->refresh();
    expect($user->provider_id)->toBeNull(); // Should not have updated

    Auth::logout();

    // 3. Enable useProviderId = true and login (Transition/Migration)
    $routerData->useProviderId = true;
    $userData->authAndRedirect($routerData);

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);

    $user->refresh();
    expect($user->provider_id)->toBe($providerId); // Migrated!

    Auth::logout();

    // 4. Subsequent login (Lookup by provider_id)
    // Change email to prove it uses provider_id (though in reality email usually stays same)
    // Actually, if we change email in UserData, it will update it on the model.
    $userData = new UserData('User One Updated', $email, '', $provider, $providerId, false);
    $userData->authAndRedirect($routerData);

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);

    Auth::logout();
});

it('prevents account takeover when providerId mismatches', function () {
    $email = 'takeover@example.com';
    $existingId = 'original-id';
    $attackerId = 'attacker-id';

    // User already has an ID
    User::factory()->create([
        'email' => $email,
        'provider_id' => $existingId,
    ]);

    $routerData = getRouterData(false);
    $routerData->useProviderId = true;

    // Attacker tries to login with same email but different ID
    $userData = new UserData('Attacker', $email, '', 'google', $attackerId, false);
    $response = $userData->authAndRedirect($routerData);

    expect(Auth::check())->toBeFalse();
    expect(customErrorType($response))->toBe('MixedProviders');
});

it('allows multiple non-exclusive providers if useProviderId is false', function () {
    $email = 'multi@example.com';

    // User exists with NO provider_id
    User::factory()->create([
        'email' => $email,
        'provider_id' => null,
    ]);

    $routerData = getRouterData(false);
    $routerData->useProviderId = false;

    // Login with Google
    $userData1 = new UserData('User Google', $email, '', 'google', 'google-id', false);
    $userData1->authAndRedirect($routerData);
    expect(Auth::check())->toBeTrue();
    Auth::logout();

    // Login with Github (simulated)
    $userData2 = new UserData('User Github', $email, '', 'github', 'github-id', false);
    $userData2->authAndRedirect($routerData);
    expect(Auth::check())->toBeTrue();
    Auth::logout();
});

it('migrates provider_id for models not implementing the interface', function () {
    class SimpleUser extends Illuminate\Foundation\Auth\User
    {
        protected $table = 'users';

        protected $fillable = ['name', 'email', 'avatar', 'provider_id'];
    }

    $this->app->config->set('auth.providers.users.model', SimpleUser::class);

    $email = 'simple@example.com';
    $providerId = 'simple-id';

    SimpleUser::create([
        'name' => 'Simple',
        'email' => $email,
        'provider_id' => null,
    ]);

    $routerData = getRouterData(false);
    $routerData->useProviderId = true;

    $userData = new UserData('Simple', $email, '', 'google', $providerId, false);
    $userData->authAndRedirect($routerData);

    expect(Auth::check())->toBeTrue();
    $user = SimpleUser::where('email', $email)->first();
    expect($user->provider_id)->toBe($providerId);
});

it('rejects new users if canAddUsers is false even with useProviderId', function () {
    $email = 'new-user@example.com';
    $providerId = 'new-id';

    $routerData = getRouterData(false); // canAddUsers = false
    $routerData->useProviderId = true;

    $userData = new UserData('New User', $email, '', 'google', $providerId, false);
    $response = $userData->authAndRedirect($routerData);

    expect(Auth::check())->toBeFalse();
    expect(customErrorType($response))->toBe('UnableToAddNewUsers');
});
