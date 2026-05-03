<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use SchenkeIo\LaravelAuthRouter\Contracts\AuthenticatableRouterUser;
use SchenkeIo\LaravelAuthRouter\Services\AppleAuthService;
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
        $mock->shouldReceive('findByEmail')->with('test@example.com')->andReturn($mock);
        $mock->shouldReceive('save')->once();

        $this->app->instance(UserWithContract::class, $mock);

        $service = new AppleAuthService;
        $token = $this->createAppleToken('apple-id', ['type' => 'consent-revoked'], 'test@example.com');

        $service->handleServerNotification(['payload' => $token]);

        $this->assertTrue(true); // Verification via Mockery
    }

    public function test_handle_apple_callback_with_contract()
    {
        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getId')->andReturn('new-apple-id');
        $appleUser->shouldReceive('getEmail')->andReturn('new@example.com');
        $appleUser->shouldReceive('getName')->andReturn('New User');

        $service = new AppleAuthService;
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

        $mock = \Mockery::mock(UserWithContract::class)->makePartial();
        $mock->shouldReceive('findByEmail')->with('existing@example.com')->andReturn($mock);

        $this->app->bind(UserWithContract::class, fn () => $mock);

        $service = new AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertSame($mock, $result);
    }

    public function test_handle_apple_callback_new_user_with_contract_and_verification()
    {
        $appleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $appleUser->shouldReceive('getId')->andReturn('brand-new-apple-id');
        $appleUser->shouldReceive('getEmail')->andReturn('brand-new@example.com');
        $appleUser->shouldReceive('getName')->andReturn('Brand New User');

        $service = new AppleAuthService;
        $result = $service->handleAppleCallback($appleUser);

        $this->assertInstanceOf(UserWithContract::class, $result);
        $this->assertEquals('Brand New User', $result->name);
        $this->assertNotNull($result->emailVerifiedAt);
    }

    public function test_handle_server_notification_with_non_contract_user()
    {
        $this->app->config->set('auth.providers.users.model', User::class);
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $service = new AppleAuthService;
        $token = $this->createAppleToken('apple-id', ['type' => 'email-disabled'], 'test@example.com');

        $service->handleServerNotification(['payload' => $token]);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_handle_server_notification_email_disabled_with_set_method()
    {
        $this->app->config->set('auth.providers.users.model', UserWithEmailSetMethod::class);
        $user = new UserWithEmailSetMethod;
        $user->email = 'test@example.com';
        $user->email_verified_at = now();
        $this->app->instance(UserWithEmailSetMethod::class, $user);

        $service = new AppleAuthService;
        $token = $this->createAppleToken('apple-id', ['type' => 'email-disabled'], 'test@example.com');
        $service->handleServerNotification(['payload' => $token]);

        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->setCalled);
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

    public function getEmail(): ?string
    {
        return $this->email;
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

    public function findByProviderId(string $providerId): ?Model
    {
        return null;
    }

    public function setProviderId(string $providerId): void {}

    public function getProviderId(): ?string
    {
        return $this->providerId;
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
