<?php

namespace SchenkeIo\LaravelAuthRouter\Services;

use Illuminate\Support\Facades\Session;
use Logto\Sdk\Storage\Storage;
use Logto\Sdk\Storage\StorageKey;
use SchenkeIo\LaravelAuthRouter\Auth\SessionKey;

/**
 * Class LogtoStorage
 *
 * Implements persistent storage for Logto SDK using Laravel's session.
 *
 * Main Responsibilities:
 * - Persist: Stores Logto state and tokens in the session.
 * - Retrieve: Fetches stored Logto data for authentication checks.
 * - Delete: Removes Logto data from the session upon logout or expiration.
 */
class LogtoStorage implements Storage
{
    public function get(StorageKey $key): ?string
    {
        return Session::get(SessionKey::LOGTO_PREFIX.$key->value);
    }

    public function set(StorageKey $key, ?string $value): void
    {
        Session::put(SessionKey::LOGTO_PREFIX.$key->value, $value);
    }

    public function delete(StorageKey $key): void
    {
        Session::forget(SessionKey::LOGTO_PREFIX.$key->value);
    }
}
