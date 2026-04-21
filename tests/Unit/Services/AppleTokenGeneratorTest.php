<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Unit\Services;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use SchenkeIo\LaravelAuthRouter\Services\AppleTokenGenerator;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;

uses(TestCase::class);

it('generates a valid jwt token for apple', function () {
    $generator = new AppleTokenGenerator;

    $teamId = 'ABCDE12345';
    $keyId = '12345ABCDE';
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

it('throws exception for invalid client id', function () {
    $generator = new AppleTokenGenerator;
    $generator->generate('ABCDE12345', '12345ABCDE', 'pk', 'invalid-client-id');
})->throws(\InvalidArgumentException::class, 'Apple Client ID must contain at least one dot');

it('throws exception for invalid team id length', function () {
    $generator = new AppleTokenGenerator;
    $generator->generate('SHORT', '12345ABCDE', 'pk', 'com.example');
})->throws(\InvalidArgumentException::class, 'Apple Team ID must be 10 characters long');

it('throws exception for invalid key id length', function () {
    $generator = new AppleTokenGenerator;
    $generator->generate('ABCDE12345', 'SHORT', 'pk', 'com.example');
})->throws(\InvalidArgumentException::class, 'Apple Key ID must be 10 characters long');

it('throws exception if private key file is missing', function () {
    $generator = new AppleTokenGenerator;
    $generator->generate('ABCDE12345', '12345ABCDE', '/path/to/missing.p8', 'com.example');
})->throws(\InvalidArgumentException::class, 'Apple private key file not found');

it('throws exception if private key file has wrong extension', function () {
    $generator = new AppleTokenGenerator;
    // create a temp file
    $path = tempnam(sys_get_temp_dir(), 'test').'.txt';
    file_put_contents($path, 'some content');

    try {
        $generator = new AppleTokenGenerator;
        $generator->generate('ABCDE12345', '12345ABCDE', $path, 'com.example');
    } finally {
        unlink($path);
    }
})->throws(\InvalidArgumentException::class, 'Apple private key file must have .p8 extension');

it('throws exception if private key is empty', function () {
    $generator = new AppleTokenGenerator;
    $generator->generate('ABCDE12345', '12345ABCDE', '', 'com.example');
})->throws(\InvalidArgumentException::class, 'Apple private key cannot be empty');

it('can load private key from file', function () {
    $privateKey = <<<'EOD'
-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIARZ6izMfM5V8TgerC5gUcT557+aaI6Oxzzs5ZNaqAtQoAoGCCqGSM49
AwEHoUQDQgAEB5bPhQ1IiHlTbcfBN6q9wjpPb8sgfFocz6zs+ANZXRR5KOUOM+Jg
uI5ZOrtAwtJE2wgRplCBjRiqdvZ6n6f4Tw==
-----END EC PRIVATE KEY-----
EOD;
    $path = tempnam(sys_get_temp_dir(), 'test').'.p8';
    file_put_contents($path, $privateKey);

    try {
        $generator = new AppleTokenGenerator;
        $tokenString = $generator->generate('ABCDE12345', '12345ABCDE', $path, 'com.example');
        expect($tokenString)->toBeString();
    } finally {
        unlink($path);
    }
});
