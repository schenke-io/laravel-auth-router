<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;
use Workbench\App\Models\User;

class AppleAuthServiceContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->config->set('auth.providers.users.model', UserWithContract::class);
        $this->app->config->set('services.apple.user_id_field', true);
    }

    public function test_handle_server_notification_with_contract()
    {
        $mock = \Mockery::mock(UserWithContract::class)->makePartial();
        $mock->shouldReceive('findByProvider')->with('apple', 'apple-id')->andReturn($mock);
        $mock->shouldReceive('setProviderId')->with('apple', '')->once();
        $mock->shouldReceive('save')->once();

        $this->app->instance(UserWithContract::class, $mock);

        // We need to trick is_subclass_of or the factory instantiation
        // Since we can't easily mock is_subclass_of, we use the real class that implements it.

        $service = new \SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
        $token = $this->createAppleToken('apple-id', ['type' => 'consent-revoked']);

        $service->handleServerNotification(['payload' => $token]);

        $this->assertTrue(true); // Verification via Mockery
    }

    public function test_handle_apple_callback_with_contract()
    {
        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getId')->andReturn('new-apple-id');
        $appleUser->shouldReceive('getEmail')->andReturn('new@example.com');
        $appleUser->shouldReceive('getName')->andReturn('New User');

        $service = new \SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertInstanceOf(UserWithContract::class, $result);
        $this->assertEquals('New User', $result->name);
        $this->assertEquals('new@example.com', $result->email);
    }

    public function test_handle_apple_callback_linking_existing_user_with_contract()
    {
        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getId')->andReturn('existing-apple-id');
        $appleUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $appleUser->shouldReceive('getName')->andReturn('Existing User');

        // Note: we can't easily mock the 'new $userModelClass' call in handleAppleCallback
        // since it's not resolved from the container when creating a new user.
        // BUT for an EXISTING user, it IS resolved via findByEmail if we implement it that way.

        $mock = \Mockery::mock(UserWithContract::class)->makePartial();
        $mock->shouldReceive('findByProvider')->with('apple', 'existing-apple-id')->andReturn(null);
        $mock->shouldReceive('findByEmail')->with('existing@example.com')->andReturn($mock);
        $mock->shouldReceive('setProviderId')->with('apple', 'existing-apple-id')->once();
        $mock->shouldReceive('save')->once();

        $this->app->bind(UserWithContract::class, fn () => $mock);

        $service = new \SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertSame($mock, $result);
    }

    public function test_handle_apple_callback_new_user_with_contract_and_verification()
    {
        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getId')->andReturn('brand-new-apple-id');
        $appleUser->shouldReceive('getEmail')->andReturn('brand-new@example.com');
        $appleUser->shouldReceive('getName')->andReturn('Brand New User');

        $service = new \SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertInstanceOf(UserWithContract::class, $result);
        $this->assertEquals('Brand New User', $result->name);
        $this->assertNotNull($result->emailVerifiedAt);
    }

    public function test_handle_server_notification_with_non_contract_user()
    {
        $this->app->config->set('auth.providers.users.model', User::class);
        $user = User::factory()->create([
            'apple_id' => 'apple-id',
            'email_verified_at' => now(),
        ]);

        $service = new \SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
        $token = $this->createAppleToken('apple-id', ['type' => 'email-disabled']);

        $service->handleServerNotification(['payload' => $token]);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_handle_server_notification_user_update_via_update_method()
    {
        // This test targets the branch where user is a Model but doesn't have email_verified_at directly or method
        $this->app->config->set('auth.providers.users.model', User::class);
        $user = User::factory()->create([
            'apple_id' => 'apple-id',
        ]);

        $service = new \SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;

        // Consent revoked
        $token = $this->createAppleToken('apple-id', ['type' => 'consent-revoked']);
        $service->handleServerNotification(['payload' => $token]);

        $user->refresh();
        $this->assertNull($user->apple_id);
    }

    public function test_handle_server_notification_email_disabled_with_set_method()
    {
        $this->app->config->set('auth.providers.users.model', UserWithEmailSetMethod::class);
        $user = new UserWithEmailSetMethod;
        $user->apple_id = 'apple-id';
        $user->email_verified_at = now();
        $this->app->instance(UserWithEmailSetMethod::class, $user);

        $service = new \SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
        $token = $this->createAppleToken('apple-id', ['type' => 'email-disabled']);
        $service->handleServerNotification(['payload' => $token]);

        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->setCalled);
    }

    private function createAppleToken(string $sub, array $event): string
    {
        $privateKey = <<<'EOD'
-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIARZ6izMfM5V8TgerC5gUcT557+aaI6Oxzzs5ZNaqAtQoAoGCCqGSM49
AwEHoUQDQgAEB5bPhQ1IiHlTbcfBN6q9wjpPb8sgfFocz6zs+ANZXRR5KOUOM+Jg
uI5ZOrtAwtJE2wgRplCBjRiqdvZ6n6f4Tw==
-----END EC PRIVATE KEY-----
EOD;

        $config = \Lcobucci\JWT\Configuration::forAsymmetricSigner(
            new \Lcobucci\JWT\Signer\Ecdsa\Sha256,
            \Lcobucci\JWT\Signer\Key\InMemory::plainText($privateKey),
            \Lcobucci\JWT\Signer\Key\InMemory::plainText('not-used')
        );

        return $config->builder()
            ->relatedTo($sub)
            ->withClaim('events', json_encode($event))
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }
}

class UserWithContract extends Authenticatable implements AuthenticatableRouterUser
{
    public $name;

    public $email;

    public $avatar;

    public $provider;

    public $providerId;

    public $emailVerifiedAt;

    public function setEmailVerifiedAt($date): void
    {
        $this->emailVerifiedAt = $date;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setAvatar(string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function findByEmail(string $email): ?Model
    {
        if (app()->bound(self::class)) {
            return app(self::class)->findByEmail($email);
        }

        return null;
    }

    public function findByProvider(string $provider, string $id): ?Model
    {
        if (app()->bound(self::class)) {
            return app(self::class)->findByProvider($provider, $id);
        }

        return null;
    }

    public function setProviderId(string $provider, string $id, ?string $fieldName = null): void
    {
        $this->providerId = $id;
    }

    public function save(array $options = [])
    {
        return true;
    }
}

class UserWithEmailSetMethod extends Authenticatable
{
    public $email_verified_at;

    public $apple_id;

    public $setCalled = false;

    public function setEmailVerifiedAt($date): void
    {
        $this->email_verified_at = $date;
        $this->setCalled = true;
    }

    public static function where($column, $value)
    {
        $mock = app(self::class);
        $builder = \Mockery::mock('Builder');
        $builder->shouldReceive('first')->andReturn($mock);

        return $builder;
    }

    public function save(array $options = [])
    {
        return true;
    }
}
