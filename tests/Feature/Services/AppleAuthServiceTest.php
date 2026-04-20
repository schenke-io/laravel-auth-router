<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;
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

class AppleAuthServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->config->set('services.apple', [
            'client_id' => 'test_client_id',
            'team_id' => 'test_team_id',
            'key_id' => 'test_key_id',
            'private_key' => 'test_private_key',
            'user_id_field' => true,
        ]);
    }

    public function test_webhook_route_is_registered_for_apple_provider()
    {
        Route::authRouter(['apple'])->success('home')->error('error')->home('home');

        $this->assertTrue(Route::has('apple.webhook'));
    }

    public function test_handle_server_notification_with_email_disabled()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->app->config->set('auth.providers.users.model', User::class);

        // Create a token with events claim
        $token = $this->createAppleToken('apple-id', ['type' => 'email-disabled'], 'test@example.com');

        Route::authRouter(['apple'])->success('home')->error('error')->home('home');

        $response = $this->post(route('apple.webhook'), [
            'payload' => $token,
        ]);

        $response->assertNoContent();

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_handle_server_notification_with_consent_revoked()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->app->config->set('auth.providers.users.model', User::class);

        $token = $this->createAppleToken('apple-id', ['type' => 'consent-revoked'], 'test@example.com');

        Route::authRouter(['apple'])->success('home')->error('error')->home('home');

        $response = $this->post(route('apple.webhook'), [
            'payload' => $token,
        ]);

        $response->assertNoContent();

        $user->refresh();
        $this->assertTrue(true);
    }

    public function test_handle_server_notification_with_empty_payload()
    {
        Route::authRouter(['apple'])->success('home')->error('error')->home('home');
        $response = $this->post(route('apple.webhook'), []);
        $response->assertNoContent();
    }

    public function test_handle_server_notification_with_invalid_token()
    {
        Route::authRouter(['apple'])->success('home')->error('error')->home('home');
        $response = $this->post(route('apple.webhook'), [
            'payload' => 'not-a-jwt',
        ]);
        $response->assertNoContent();
    }

    public function test_handle_server_notification_with_missing_sub()
    {
        Route::authRouter(['apple'])->success('home')->error('error')->home('home');
        // Token without 'sub' claim (relatedTo in builder sets sub)
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

        $response = $this->post(route('apple.webhook'), [
            'payload' => $token,
        ]);
        $response->assertNoContent();
    }

    public function test_handle_server_notification_with_unknown_user()
    {
        Route::authRouter(['apple'])->success('home')->error('error')->home('home');
        $token = $this->createAppleToken('unknown-apple-id', ['type' => 'email-disabled'], 'unknown@example.com');

        $response = $this->post(route('apple.webhook'), [
            'payload' => $token,
        ]);
        $response->assertNoContent();
    }

    public function test_handle_apple_callback_returning_user()
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'name' => 'Old Name',
        ]);

        $this->app->config->set('auth.providers.users.model', User::class);

        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getId')->andReturn('apple-id');
        $appleUser->shouldReceive('getEmail')->andReturn('old@example.com');
        $appleUser->shouldReceive('getName')->andReturn(null);

        $service = new AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertEquals($user->id, $result->id);
        $this->assertEquals('old@example.com', $result->email);
        $this->assertEquals('Old Name', $result->name);
    }

    public function test_handle_apple_callback_link_existing_email()
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this->app->config->set('auth.providers.users.model', User::class);

        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getId')->andReturn('apple-id');
        $appleUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $appleUser->shouldReceive('getName')->andReturn('New Name');

        $service = new AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertEquals($user->id, $result->id);
    }

    public function test_handle_apple_callback_new_user()
    {
        $this->app->config->set('auth.providers.users.model', User::class);

        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getId')->andReturn('new-apple-id');
        $appleUser->shouldReceive('getEmail')->andReturn('new@example.com');
        $appleUser->shouldReceive('getName')->andReturn('New User');

        $service = new AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('new@example.com', $result->email);
        $this->assertEquals('New User', $result->name);
        $this->assertNotNull($result->email_verified_at);
    }

    private function createAppleToken(string $sub, array $event, string $email = ''): string
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

        return $builder->getToken($config->signer(), $config->signingKey())
            ->toString();
    }

    public function test_handle_server_notification_with_exception()
    {
        Log::shouldReceive('error')->once();

        $service = new AppleAuthService;
        $service->handleServerNotification(['payload' => 'invalid-token']);
    }

    public function test_handle_server_notification_with_minimal_model()
    {
        // Line 72-73: email-disabled fallback for Model
        $user = User::factory()->create([
            'email' => 'minimal@example.com',
            'email_verified_at' => now(),
        ]);

        $this->app->config->set('auth.providers.users.model', MinimalUser::class);

        $token = $this->createAppleToken('apple-id', ['type' => 'email-disabled'], 'minimal@example.com');

        Route::authRouter(['apple'])->success('home')->error('error')->home('home');

        $response = $this->post(route('apple.webhook'), [
            'payload' => $token,
        ]);

        $response->assertNoContent();
        $user->refresh();
        $this->assertNull($user->email_verified_at);

        // Line 83-84: consent-revoked fallback for Model
        $token = $this->createAppleToken('apple-id', ['type' => 'consent-revoked'], 'minimal@example.com');

        $response = $this->post(route('apple.webhook'), [
            'payload' => $token,
        ]);

        $response->assertNoContent();
        $user->refresh();
        $this->assertTrue(true);
    }

    public function test_handle_server_notification_with_model_with_properties()
    {
        // Line 70-71: email-disabled fallback for Model
        $user = User::factory()->create([
            'email' => 'props@example.com',
            'email_verified_at' => now(),
        ]);

        $this->app->config->set('auth.providers.users.model', ModelWithProperties::class);

        $token = $this->createAppleToken('apple-id', ['type' => 'email-disabled'], 'props@example.com');

        Route::authRouter(['apple'])->success('home')->error('error')->home('home');

        $this->post(route('apple.webhook'), [
            'payload' => $token,
        ])->assertNoContent();

        $user->refresh();
        $this->assertNull($user->email_verified_at);

        // Line 81-82: consent-revoked fallback for Model
        $token = $this->createAppleToken('apple-id', ['type' => 'consent-revoked'], 'props@example.com');

        $this->post(route('apple.webhook'), [
            'payload' => $token,
        ])->assertNoContent();

        $user->refresh();
        $this->assertTrue(true);
    }

    public function test_handle_apple_callback_returning_user_with_minimal_model()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->app->config->set('auth.providers.users.model', MinimalUser::class);

        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getId')->andReturn('apple-id');
        $appleUser->shouldReceive('getEmail')->andReturn('test@example.com');
        $appleUser->shouldReceive('getName')->andReturn(null);

        $service = new AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertEquals($user->id, $result->id);
    }

    public function test_handle_apple_callback_link_existing_email_with_minimal_model()
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this->app->config->set('auth.providers.users.model', ModelWithProperties::class);

        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getId')->andReturn('apple-id');
        $appleUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $appleUser->shouldReceive('getName')->andReturn('New Name');

        $service = new AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertEquals($user->id, $result->id);
    }

    public function test_handle_apple_callback_new_user_with_minimal_model()
    {
        $this->app->config->set('auth.providers.users.model', MinimalUser::class);

        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getId')->andReturn('new-apple-id');
        $appleUser->shouldReceive('getEmail')->andReturn('new@example.com');
        $appleUser->shouldReceive('getName')->andReturn('New User');

        $service = new AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertInstanceOf(MinimalUser::class, $result);
        $this->assertEquals('new@example.com', $result->email);
        $this->assertEquals('New User', $result->name);
        $this->assertNotNull($result->email_verified_at);
    }

    public function test_handle_apple_callback_with_empty_email()
    {
        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getEmail')->andReturn('');
        $appleUser->shouldReceive('getName')->andReturn('Some Name');

        $service = new AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertNull($result);
    }
}
