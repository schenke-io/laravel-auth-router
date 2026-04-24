<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use Illuminate\Support\Collection;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use SchenkeIo\LaravelAuthRouter\Auth\Service;
use SchenkeIo\LaravelAuthRouter\Contracts\UseExclusiveInterface;
use SchenkeIo\LaravelAuthRouter\LoginProviders\UnknownBaseProvider;

/**
 * A collection for login providers and factory to create them from text.
 *
 * @extends Collection<int, BaseProvider>
 */
class ProviderCollection extends Collection
{
    /**
     * @param  string|array<int, string|BaseProvider>  $data
     */
    public static function fromTextArray(string|array $data): ProviderCollection
    {
        $me = new self([]);
        $data = is_string($data) ? [$data] : $data;

        foreach ($data as $item) {
            $me->push(self::createProviderFromText($item));
        }

        return $me->handleExclusivity()->sortProviders();
    }

    private function handleExclusivity(): self
    {
        $exclusive = $this->first(fn (BaseProvider $p) => $p instanceof UseExclusiveInterface);
        if ($exclusive) {
            $others = $this->filter(fn (BaseProvider $p) => $p->isSocial() && $p !== $exclusive);
            if ($others->count() > 0) {
                foreach ($others as $p) {
                    $p->addError(Error::ExclusiveProvider->trans(['name' => ucfirst($exclusive->name)]));
                }
            }
        }

        return $this;
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

    private function sortProviders(): self
    {
        return $this->sortBy(function (BaseProvider $provider) {
            if (! $provider->isSocial()) {
                return 1;
            }

            return 10;
        })->values();
    }

    public function first(?callable $callback = null, $default = null): ?BaseProvider
    {
        return parent::first($callback, $default);
    }
}
