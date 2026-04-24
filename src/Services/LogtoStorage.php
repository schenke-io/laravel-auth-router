<?php

namespace SchenkeIo\LaravelAuthRouter\Services;

use Illuminate\Support\Facades\Session;
use Logto\Sdk\Storage\Storage;
use Logto\Sdk\Storage\StorageKey;

class LogtoStorage implements Storage
{
    public function get(StorageKey $key): ?string
    {
        return Session::get('logto_'.$key->value);
    }

    public function set(StorageKey $key, ?string $value): void
    {
        Session::put('logto_'.$key->value, $value);
    }

    public function delete(StorageKey $key): void
    {
        Session::forget('logto_'.$key->value);
    }
}
