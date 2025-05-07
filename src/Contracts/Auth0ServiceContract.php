<?php

namespace SchenkeIo\LaravelAuthRouter\Contracts;

use Auth0\SDK\Exception\ConfigurationException;
use Auth0\SDK\Exception\NetworkException;
use Auth0\SDK\Exception\StateException;

interface Auth0ServiceContract
{
    /**
     * @param  array<string,string>  $parameter
     *
     * @throws ConfigurationException
     */
    public function login(string $redirectUri, array $parameter): string;

    /**
     * authenticate the user
     *
     * @throws StateException
     * @throws NetworkException
     */
    public function exchange(): void;

    /**
     * @return array<mixed>|null
     */
    public function getUser(): ?array;
}
