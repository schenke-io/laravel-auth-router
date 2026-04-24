<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Unit\Services;

use Illuminate\Support\Facades\Session;
use Logto\Sdk\Storage\StorageKey;
use SchenkeIo\LaravelAuthRouter\Services\LogtoStorage;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;

class LogtoStorageTest extends TestCase
{
    public function test_it_can_get_set_and_delete_session_data()
    {
        $storage = new LogtoStorage;
        $key = StorageKey::idToken;
        $value = 'some_token';

        $storage->set($key, $value);
        $this->assertEquals($value, Session::get('logto_'.$key->value));
        $this->assertEquals($value, $storage->get($key));

        $storage->delete($key);
        $this->assertNull($storage->get($key));
        $this->assertFalse(Session::has('logto_'.$key->value));
    }
}
