<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Unit\Services;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use SchenkeIo\LaravelAuthRouter\Services\AppleTokenGenerator;

it('generates a valid jwt token for apple', function () {
    $generator = new AppleTokenGenerator;

    $teamId = 'MYTEAMID';
    $keyId = 'MYKEYID';
    $clientId = 'com.example.service';
    $privateKey = <<<'EOD'
-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIARZ6izMfM5V8TgerC5gUcT557+aaI6Oxzzs5ZNaqAtQoAoGCCqGSM49
AwEHoUQDQgAEB5bPhQ1IiHlTbcfBN6q9wjpPb8sgfFocz6zs+ANZXRR5KOUOM+Jg
uI5ZOrtAwtJE2wgRplCBjRiqdvZ6n6f4Tw==
-----END EC PRIVATE KEY-----
EOD;

    $tokenString = $generator->generate($teamId, $keyId, $privateKey, $clientId);

    expect($tokenString)->toBeString();

    $parser = new Parser(new JoseEncoder);
    $token = $parser->parse($tokenString);

    $claims = $token->claims();
    $headers = $token->headers();

    expect($headers->get('kid'))->toBe($keyId);
    expect($claims->get('iss'))->toBe($teamId);
    expect($claims->get('aud'))->toContain('https://appleid.apple.com');
    expect($claims->get('sub'))->toBe($clientId);

    $now = new \DateTimeImmutable;
    expect($claims->get('iat')->getTimestamp())->toBeLessThanOrEqual($now->getTimestamp());
    expect($claims->get('exp')->getTimestamp())->toBeGreaterThan($now->getTimestamp());
});

it('throws exception for empty credentials', function () {
    $generator = new AppleTokenGenerator;
    $generator->generate('', 'key', 'pk', 'client');
})->throws(\InvalidArgumentException::class, 'Apple credentials cannot be empty');
