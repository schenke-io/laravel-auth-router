<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use Illuminate\Support\Collection;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use SchenkeIo\LaravelAuthRouter\Auth\Service;
use SchenkeIo\LaravelAuthRouter\LoginProviders\UnknownBaseProvider;

/**
 * @extends Collection<int, BaseProvider>
 */
class ProviderCollection extends Collection
{
    /**
     * @param  string|string[]  $data
     */
    public static function fromTextArray(string|array $data): ProviderCollection
    {
        $me = new self([]);
        if (is_string($data)) {
            $data = [$data];
        }
        foreach ($data as $name) {
            $configKey = 'services.'.$name;
            $service = Service::get($name);
            if ($service) {
                $provider = $service->provider();
            } else {
                // error
                $provider = new UnknownBaseProvider;
                $provider->addError(Error::UnknownService->trans(['name' => $name]));
            }
            $me->push($provider);
        }

        return $me;
    }

    public function first(?callable $callback = null, $default = null): BaseProvider
    {
        return $this->items[0] ?? new UnknownBaseProvider;
    }
}
