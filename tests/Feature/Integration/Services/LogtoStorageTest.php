<?php

pest()->group('feature');

use Illuminate\Support\Facades\Session;
use Logto\Sdk\Storage\StorageKey;
use SchenkeIo\LaravelAuthRouter\Services\LogtoStorage;

it('can get set and delete session data', function () {
    $storage = new LogtoStorage;
    $key = StorageKey::idToken;
    $value = 'some_token';

    $storage->set($key, $value);
    expect(Session::get('logto_'.$key->value))->toBe($value)
        ->and($storage->get($key))->toBe($value);

    $storage->delete($key);
    expect($storage->get($key))->toBeNull()
        ->and(Session::has('logto_'.$key->value))->toBeFalse();
});
