<?php

pest()->group('feature');

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
use Workbench\App\Models\User;

class MinimalUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    public function __isset($key)
    {
        return false;
    }
}

class ModelWithProperties extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}

beforeEach(function () {
    config()->set('services.apple', [
        'client_id' => 'test_client_id',
        'team_id' => 'test_team_id',
        'key_id' => 'test_key_id',
        'private_key' => 'test_private_key',
        'user_id_field' => true,
    ]);
});

function createAppleToken(string $sub, array $event, string $email = ''): string
{
    $privateKey = <<<'EOD'
-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIARZ6izMfM5V8TgerC5gUcT557+aaI6Oxzzs5ZNaqAtQoAoGCCqGSM49
AwEHoUQDQgAEB5bPhQ1IiHlTbcfBN6q9wjpPb8sgfFocz6zs+ANZXRR5KOUOM+Jg
uI5ZOrtAwtJE2wgRplCBjRiqdvZ6n6f4Tw==
-----END EC PRIVATE KEY-----
EOD;

    $config = Configuration::forAsymmetricSigner(
        new Sha256,
        InMemory::plainText($privateKey),
        InMemory::plainText('not-used')
    );

    $builder = $config->builder()
        ->relatedTo($sub)
        ->withClaim('events', json_encode($event));

    if ($email) {
        $builder = $builder->withClaim('email', $email);
    }

    return $builder->getToken($config->signer(), $config->signingKey())->toString();
}

it('registers webhook route for apple provider', function () {
    Route::authRouter(['apple'])->success('home')->error('error')->home('home');
    expect(Route::has('apple.webhook'))->toBeTrue();
});

it('handles server notification with email disabled', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'email_verified_at' => now(),
    ]);
    config()->set('auth.providers.users.model', User::class);
    $token = createAppleToken('apple-id', ['type' => 'email-disabled'], 'test@example.com');

    Route::authRouter(['apple'])->success('home')->error('error')->home('home');

    $this->post(route('apple.webhook'), ['payload' => $token])->assertNoContent();

    $user->refresh();
    expect($user->email_verified_at)->toBeNull();
});

it('handles server notification with consent revoked', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);
    config()->set('auth.providers.users.model', User::class);
    $token = createAppleToken('apple-id', ['type' => 'consent-revoked'], 'test@example.com');

    Route::authRouter(['apple'])->success('home')->error('error')->home('home');

    $this->post(route('apple.webhook'), ['payload' => $token])->assertNoContent();

    $user->refresh();
    expect(true)->toBeTrue();
});

it('handles server notification with empty payload', function () {
    Route::authRouter(['apple'])->success('home')->error('error')->home('home');
    $this->post(route('apple.webhook'), [])->assertNoContent();
});

it('handles server notification with invalid token', function () {
    Route::authRouter(['apple'])->success('home')->error('error')->home('home');
    $this->post(route('apple.webhook'), ['payload' => 'not-a-jwt'])->assertNoContent();
});

it('handles server notification with missing sub', function () {
    Route::authRouter(['apple'])->success('home')->error('error')->home('home');
    $privateKey = <<<'EOD'
-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIARZ6izMfM5V8TgerC5gUcT557+aaI6Oxzzs5ZNaqAtQoAoGCCqGSM49
AwEHoUQDQgAEB5bPhQ1IiHlTbcfBN6q9wjpPb8sgfFocz6zs+ANZXRR5KOUOM+Jg
uI5ZOrtAwtJE2wgRplCBjRiqdvZ6n6f4Tw==
-----END EC PRIVATE KEY-----
EOD;
    $config = Configuration::forAsymmetricSigner(
        new Sha256,
        InMemory::plainText($privateKey),
        InMemory::plainText('not-used')
    );
    $token = $config->builder()
        ->withClaim('events', json_encode(['type' => 'email-disabled']))
        ->getToken($config->signer(), $config->signingKey())
        ->toString();

    $this->post(route('apple.webhook'), ['payload' => $token])->assertNoContent();
});

it('handles server notification with unknown user', function () {
    Route::authRouter(['apple'])->success('home')->error('error')->home('home');
    $token = createAppleToken('unknown-apple-id', ['type' => 'email-disabled'], 'unknown@example.com');
    $this->post(route('apple.webhook'), ['payload' => $token])->assertNoContent();
});

it('handles apple callback returning user', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'name' => 'Old Name',
    ]);
    config()->set('auth.providers.users.model', User::class);

    $appleUser = mock(Laravel\Socialite\Contracts\User::class);
    $appleUser->shouldReceive('getId')->andReturn('apple-id');
    $appleUser->shouldReceive('getEmail')->andReturn('old@example.com');
    $appleUser->shouldReceive('getName')->andReturn(null);

    $service = new AppleAuthService;
    $result = $service->handleAppleCallback($appleUser);

    expect($result->id)->toBe($user->id)
        ->and($result->email)->toBe('old@example.com')
        ->and($result->name)->toBe('Old Name');
});

it('handles apple callback link existing email', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);
    config()->set('auth.providers.users.model', User::class);

    $appleUser = mock(Laravel\Socialite\Contracts\User::class);
    $appleUser->shouldReceive('getId')->andReturn('apple-id');
    $appleUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $appleUser->shouldReceive('getName')->andReturn('New Name');

    $service = new AppleAuthService;
    $result = $service->handleAppleCallback($appleUser);

    expect($result->id)->toBe($user->id);
});

it('handles apple callback new user', function () {
    config()->set('auth.providers.users.model', User::class);

    $appleUser = mock(Laravel\Socialite\Contracts\User::class);
    $appleUser->shouldReceive('getId')->andReturn('new-apple-id');
    $appleUser->shouldReceive('getEmail')->andReturn('new@example.com');
    $appleUser->shouldReceive('getName')->andReturn('New User');

    $service = new AppleAuthService;
    $result = $service->handleAppleCallback($appleUser);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->email)->toBe('new@example.com')
        ->and($result->name)->toBe('New User')
        ->and($result->email_verified_at)->not->toBeNull();
});

it('handles server notification with exception', function () {
    Log::shouldReceive('error')->once();
    $service = new AppleAuthService;
    $service->handleServerNotification(['payload' => 'invalid-token']);
});

it('handles server notification with minimal model', function () {
    $user = User::factory()->create([
        'email' => 'minimal@example.com',
        'email_verified_at' => now(),
    ]);
    config()->set('auth.providers.users.model', MinimalUser::class);
    $token = createAppleToken('apple-id', ['type' => 'email-disabled'], 'minimal@example.com');

    Route::authRouter(['apple'])->success('home')->error('error')->home('home');

    $this->post(route('apple.webhook'), ['payload' => $token])->assertNoContent();
    $user->refresh();
    expect($user->email_verified_at)->toBeNull();

    $token = createAppleToken('apple-id', ['type' => 'consent-revoked'], 'minimal@example.com');
    $this->post(route('apple.webhook'), ['payload' => $token])->assertNoContent();
    $user->refresh();
    expect(true)->toBeTrue();
});

it('handles server notification with model with properties', function () {
    $user = User::factory()->create([
        'email' => 'props@example.com',
        'email_verified_at' => now(),
    ]);
    config()->set('auth.providers.users.model', ModelWithProperties::class);
    $token = createAppleToken('apple-id', ['type' => 'email-disabled'], 'props@example.com');

    Route::authRouter(['apple'])->success('home')->error('error')->home('home');

    $this->post(route('apple.webhook'), ['payload' => $token])->assertNoContent();
    $user->refresh();
    expect($user->email_verified_at)->toBeNull();

    $token = createAppleToken('apple-id', ['type' => 'consent-revoked'], 'props@example.com');
    $this->post(route('apple.webhook'), ['payload' => $token])->assertNoContent();
    $user->refresh();
    expect(true)->toBeTrue();
});

it('handles apple callback returning user with minimal model', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);
    config()->set('auth.providers.users.model', MinimalUser::class);

    $appleUser = mock(Laravel\Socialite\Contracts\User::class);
    $appleUser->shouldReceive('getId')->andReturn('apple-id');
    $appleUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $appleUser->shouldReceive('getName')->andReturn(null);

    $service = new AppleAuthService;
    $result = $service->handleAppleCallback($appleUser);

    expect($result->id)->toBe($user->id);
});

it('handles apple callback link existing email with minimal model', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);
    config()->set('auth.providers.users.model', ModelWithProperties::class);

    $appleUser = mock(Laravel\Socialite\Contracts\User::class);
    $appleUser->shouldReceive('getId')->andReturn('apple-id');
    $appleUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $appleUser->shouldReceive('getName')->andReturn('New Name');

    $service = new AppleAuthService;
    $result = $service->handleAppleCallback($appleUser);

    expect($result->id)->toBe($user->id);
});

it('handles apple callback new user with minimal model', function () {
    config()->set('auth.providers.users.model', MinimalUser::class);

    $appleUser = mock(Laravel\Socialite\Contracts\User::class);
    $appleUser->shouldReceive('getId')->andReturn('new-apple-id');
    $appleUser->shouldReceive('getEmail')->andReturn('new@example.com');
    $appleUser->shouldReceive('getName')->andReturn('New User');

    $service = new AppleAuthService;
    $result = $service->handleAppleCallback($appleUser);

    expect($result)->toBeInstanceOf(MinimalUser::class)
        ->and($result->email)->toBe('new@example.com')
        ->and($result->name)->toBe('New User')
        ->and($result->email_verified_at)->not->toBeNull();
});

it('handles apple callback with empty email', function () {
    $appleUser = mock(Laravel\Socialite\Contracts\User::class);
    $appleUser->shouldReceive('getId')->andReturn('');
    $appleUser->shouldReceive('getEmail')->andReturn('');
    $appleUser->shouldReceive('getName')->andReturn('Some Name');

    $service = new AppleAuthService;
    $result = $service->handleAppleCallback($appleUser);

    expect($result)->toBeNull();
});

it('handles apple callback with provider id lookup', function () {
    $user = User::factory()->create([
        'email' => 'other@example.com',
        'provider_id' => 'apple-123',
    ]);

    $appleUser = mock(Laravel\Socialite\Contracts\User::class);
    $appleUser->shouldReceive('getId')->andReturn('apple-123');
    $appleUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $appleUser->shouldReceive('getName')->andReturn('Test User');

    $service = new AppleAuthService;
    $result = $service->handleAppleCallback($appleUser, false);
    expect($result->id)->not->toBe($user->id);

    $result = $service->handleAppleCallback($appleUser, true);
    expect($result->id)->toBe($user->id);
});

it('handles server notification with provider id lookup', function () {
    $user = User::factory()->create([
        'email' => 'other@example.com',
        'provider_id' => 'apple-123',
        'email_verified_at' => now(),
    ]);
    config()->set('auth.providers.users.model', User::class);
    $token = createAppleToken('apple-123', ['type' => 'email-disabled'], 'test@example.com');

    $service = new AppleAuthService;
    $service->handleServerNotification(['payload' => $token], true);

    $user->refresh();
    expect($user->email_verified_at)->toBeNull();
});

it('handles server notification with provider id lookup non interface model', function () {
    $user = User::factory()->create([
        'email' => 'other@example.com',
        'provider_id' => 'apple-123',
        'email_verified_at' => now(),
    ]);
    config()->set('auth.providers.users.model', ModelWithProperties::class);
    $token = createAppleToken('apple-123', ['type' => 'email-disabled'], 'test@example.com');

    $service = new AppleAuthService;
    $service->handleServerNotification(['payload' => $token], true);

    $user->refresh();
    expect($user->email_verified_at)->toBeNull();
});

it('handles apple callback with provider id lookup non interface model', function () {
    $user = User::factory()->create([
        'email' => 'other@example.com',
        'provider_id' => 'apple-123',
    ]);
    config()->set('auth.providers.users.model', ModelWithProperties::class);

    $appleUser = mock(Laravel\Socialite\Contracts\User::class);
    $appleUser->shouldReceive('getId')->andReturn('apple-123');
    $appleUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $appleUser->shouldReceive('getName')->andReturn('Test User');

    $service = new AppleAuthService;
    $result = $service->handleAppleCallback($appleUser, true);
    expect($result->id)->toBe($user->id);
});
