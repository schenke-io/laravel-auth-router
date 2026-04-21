<?php

namespace SchenkeIo\LaravelAuthRouter\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\File;
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
        if (! str_contains($clientId, '.')) {
            throw new \InvalidArgumentException('Apple Client ID must contain at least one dot');
        }
        if (strlen($teamId) !== 10) {
            throw new \InvalidArgumentException('Apple Team ID must be 10 characters long');
        }
        if (strlen($keyId) !== 10) {
            throw new \InvalidArgumentException('Apple Key ID must be 10 characters long');
        }

        if (str_contains($privateKey, '/')) {
            if (! File::exists($privateKey)) {
                throw new \InvalidArgumentException('Apple private key file not found');
            }
            if (! str_ends_with($privateKey, '.p8')) {
                throw new \InvalidArgumentException('Apple private key file must have .p8 extension');
            }
            $privateKey = File::get($privateKey);
        }

        if ($privateKey === '') {
            throw new \InvalidArgumentException('Apple private key cannot be empty');
        }

        /** @var non-falsy-string $privateKey */
        /** @var non-falsy-string $teamId */
        /** @var non-falsy-string $keyId */
        /** @var non-falsy-string $clientId */
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
