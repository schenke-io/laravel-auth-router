<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use Illuminate\Support\Collection;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
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
    public function __construct(string|array $data)
    {
        if (is_string($data)) {
            $data = [$data];
        }
        parent::__construct();
        foreach ($data as $name) {
            $service = Service::get($name);
            if ($service) {
                $provider = $service->provider();
            } else {
                // error
                $provider = new UnknownBaseProvider;
                $provider->addError(__('auth-router::errors.provider_not_found', ['provider' => $name]));
            }
            $this->push($provider);
        }
    }

    public function first(?callable $callback = null, $default = null): BaseProvider
    {
        return $this->items[0] ?? new UnknownBaseProvider;
    }
}
