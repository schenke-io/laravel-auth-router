<?php

use SchenkeIo\LaravelAuthRouter\Auth\Service;

function getLanguages(): array
{
    return ['de', 'en'];
}

it('has all keys translated', function () {
    foreach (['de', 'en'] as $lang) {
        $fileName = __DIR__.'/../../../resources/lang/'.$lang.'/errors.php';
        $data = require $fileName;
        foreach (\SchenkeIo\LaravelAuthRouter\Auth\Error::cases() as $case) {
            // is the key defined
            $this->assertArrayHasKey($case->name, $data);
            foreach ($case->parameterKeys() as $key) {
                $translation = $data[$case->name] ?? '';
                if (is_string($translation)) {
                    // does the translation has the key
                    $this->assertStringContainsString(':'.$key, $translation);
                }
            }
        }
        $fileName = __DIR__.'/../../../resources/lang/'.$lang.'/login.php';
        $data = require $fileName;
        $keys = array_merge(['back'], array_map(
            fn ($case) => $case->name, Service::cases())
        );
        //        dd($keys);
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }
});
