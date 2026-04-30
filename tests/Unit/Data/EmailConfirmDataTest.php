<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Unit\Data;

use Carbon\Carbon;
use SchenkeIo\LaravelAuthRouter\Data\EmailConfirmData;

it('can be instantiated and retrieved', function () {
    $expiresAt = Carbon::now()->addHour();
    $data = new EmailConfirmData(
        'test@example.com',
        'token123',
        'John',
        'Doe',
        $expiresAt
    );

    expect($data->getEmail())->toBe('test@example.com')
        ->and($data->getToken())->toBe('token123')
        ->and($data->getData())->toBe([
            'email' => 'test@example.com',
            'token' => 'token123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
});

it('can be restored via __set_state', function () {
    $expiresAt = Carbon::now()->addHour();
    $properties = [
        'email' => 'test@example.com',
        'token' => 'token123',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'expires_at' => $expiresAt,
    ];

    $data = EmailConfirmData::__set_state($properties);

    expect($data)->toBeInstanceOf(EmailConfirmData::class)
        ->and($data->email)->toBe('test@example.com')
        ->and($data->token)->toBe('token123')
        ->and($data->first_name)->toBe('John')
        ->and($data->last_name)->toBe('Doe')
        ->and($data->expires_at)->toBe($expiresAt);
});
