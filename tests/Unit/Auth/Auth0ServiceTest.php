<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Unit\Auth;

use Auth0\SDK\Auth0 as Auth0Sdk; // The final class
use Auth0\SDK\Exception\ConfigurationException;
use Auth0\SDK\Exception\NetworkException;
use Auth0\SDK\Exception\StateException;
use Mockery; // Import Mockery
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration; // For easy integration with PHPUnit
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SchenkeIo\LaravelAuthRouter\Auth\Auth0Service;

class Auth0ServiceTest extends TestCase
{
    // This trait handles Mockery::close() automatically after each test
    // and integrates Mockery's expectations with PHPUnit assertions.
    use MockeryPHPUnitIntegration;

    /**
     * Declare the mock property.
     * The type hint can be the original class, Mockery\MockInterface, or both.
     * Using `Auth0Sdk|Mockery\MockInterface` provides good autocompletion.
     *
     * @var Auth0Sdk|Mockery\MockInterface
     */
    private $auth0SdkMock;

    private Auth0Service $auth0Service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an "alias mock" for the final Auth0Sdk class.
        // Mockery will effectively replace Auth0Sdk with this mock definition
        // for code that uses it hereafter in this test.
        // Important: This is powerful. Be aware of its scope.
        // Since Auth0Sdk is injected via constructor, it's well-contained here.
        $this->auth0SdkMock = Mockery::mock('alias:'.Auth0Sdk::class);

        // Instantiate your service with the mock
        $this->auth0Service = new Auth0Service($this->auth0SdkMock);
    }

    // No explicit tearDown() needed for Mockery::close() if using MockeryPHPUnitIntegration trait.

    /**
     * @throws ConfigurationException
     */
    #[Test]
    public function login_should_call_sdk_login_and_return_url(): void
    {
        $redirectUri = 'https://myapp.com/callback';
        $parameters = ['scope' => 'openid profile email'];
        $expectedLoginUrl = 'https://auth0.com/authorize?...'; // Example URL

        // Set up expectation on the mock
        $this->auth0SdkMock
            ->shouldReceive('login') // Expect 'login' method to be called
            ->once()                // Exactly one time
            ->with($redirectUri, $parameters) // With these specific arguments
            ->andReturn($expectedLoginUrl);   // And it should return this value

        $loginUrl = $this->auth0Service->login($redirectUri, $parameters);

        $this->assertEquals($expectedLoginUrl, $loginUrl);
    }

    #[Test]
    public function login_should_throw_configuration_exception_if_sdk_throws_it(): void
    {
        $redirectUri = 'https://myapp.com/callback';
        $parameters = [];

        $this->auth0SdkMock
            ->shouldReceive('login')
            ->once()
            ->with($redirectUri, $parameters)
            ->andThrow(new ConfigurationException('SDK Config Error')); // Make the mock throw the exception

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('SDK Config Error');

        $this->auth0Service->login($redirectUri, $parameters);
    }

    /**
     * @throws NetworkException
     * @throws StateException
     */
    #[Test]
    public function exchange_should_call_sdk_exchange(): void
    {
        $this->auth0SdkMock
            ->shouldReceive('exchange')
            ->once(); // No return value, just verify it's called

        $this->auth0Service->exchange();
    }

    #[Test]
    public function exchange_should_throw_network_exception_if_sdk_throws_it(): void
    {
        $this->auth0SdkMock
            ->shouldReceive('exchange')
            ->once()
            ->andThrow(new NetworkException('SDK Network Error'));

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('SDK Network Error');

        $this->auth0Service->exchange();
    }

    #[Test]
    public function exchange_should_throw_state_exception_if_sdk_throws_it(): void
    {
        $this->auth0SdkMock
            ->shouldReceive('exchange')
            ->once()
            ->andThrow(new StateException('SDK State Error'));

        $this->expectException(StateException::class);
        $this->expectExceptionMessage('SDK State Error');

        $this->auth0Service->exchange();
    }

    #[Test]
    public function get_user_should_return_user_data_from_sdk(): void
    {
        $expectedUserData = [
            'sub' => 'auth0|1234567890',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ];

        $this->auth0SdkMock
            ->shouldReceive('getUser')
            ->once()
            ->andReturn($expectedUserData);

        $userData = $this->auth0Service->getUser();

        $this->assertEquals($expectedUserData, $userData);
    }

    #[Test]
    public function get_user_should_return_null_if_sdk_returns_null(): void
    {
        $this->auth0SdkMock
            ->shouldReceive('getUser')
            ->once()
            ->andReturn(null);

        $userData = $this->auth0Service->getUser();

        $this->assertNull($userData);
    }
}
