<?php

namespace SchenkeIo\LaravelAuthRouter\Services;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

/**
 * Service to generate dynamic Apple client secrets.
 *
 * Apple requires a JWT as a client secret for Socialite authentication.
 * This service uses the lcobucci/jwt library to create this token
 * using the Apple developer credentials.
 */
class AppleTokenGenerator
{
    /**
     * Generate a JWT for Apple client secret.
     *
     * @param  string  $teamId  Apple Team ID
     * @param  string  $keyId  Apple Key ID
     * @param  string  $privateKey  Apple Private Key (content)
     * @param  string  $clientId  Apple Client ID (Service ID)
     */
    public function generate(string $teamId, string $keyId, string $privateKey, string $clientId): string
    {
        if ($teamId === '' || $keyId === '' || $privateKey === '' || $clientId === '') {
            throw new \InvalidArgumentException('Apple credentials cannot be empty');
        }

        /** @var non-empty-string $teamId */
        /** @var non-empty-string $keyId */
        /** @var non-empty-string $privateKey */
        /** @var non-empty-string $clientId */
        $config = Configuration::forAsymmetricSigner(
            new Sha256,
            InMemory::plainText($privateKey),
            InMemory::plainText('not-used')
        );

        $now = new DateTimeImmutable;

        return $config->builder()
            ->issuedBy($teamId)
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->permittedFor('https://appleid.apple.com')
            ->relatedTo($clientId)
            ->withHeader('kid', $keyId)
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }
}
