<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use Auth0\SDK\Auth0 as Auth0Sdk;
use Auth0\SDK\Exception\ConfigurationException;
use Auth0\SDK\Exception\NetworkException;
use Auth0\SDK\Exception\StateException;
use SchenkeIo\LaravelAuthRouter\Contracts\Auth0ServiceContract;

class Auth0Service implements Auth0ServiceContract
{
    public function __construct(protected Auth0Sdk $auth0) {}

    /**
     * @param  array<string,string>  $parameter
     *
     * @throws ConfigurationException
     */
    public function login(string $redirectUri, array $parameter): string
    {
        return $this->auth0->login($redirectUri, $parameter);
    }

    /**
     * @throws NetworkException
     * @throws StateException
     */
    public function exchange(): void
    {
        $this->auth0->exchange();
    }

    /**
     * @return array<string,string>|null
     */
    public function getUser(): ?array
    {
        return $this->auth0->getUser();
    }
}
