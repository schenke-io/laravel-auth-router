<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Enums\Error;
use SchenkeIo\LaravelAuthRouter\Enums\Service;
use SchenkeIo\LaravelAuthRouter\LoginProviders\UnknownBaseProvider;

class ProviderFactory
{
    /**
     * @param  string|array<int, string|BaseProvider>  $data
     */
    public static function fromTextArray(string|array $data): ProviderCollection
    {
        $collection = new ProviderCollection([]);
        $data = is_string($data) ? [$data] : $data;

        foreach ($data as $item) {
            $collection->push(self::createProviderFromText($item));
        }

        return $collection->handleExclusivity()->sortProviders();
    }

    private static function createProviderFromText(string|BaseProvider $item): BaseProvider
    {
        if ($item instanceof BaseProvider) {
            return $item;
        }
        if (class_exists($item) && is_subclass_of($item, BaseProvider::class)) {
            return new $item;
        }
        $service = Service::get($item);
        if ($service) {
            return $service->provider();
        }
        // error
        $provider = new UnknownBaseProvider;
        $provider->addError(Error::UnknownService->trans(['name' => $item]));

        return $provider;
    }
}
