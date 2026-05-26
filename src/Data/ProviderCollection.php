<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use Illuminate\Support\Collection;
use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;
use SchenkeIo\LaravelAuthRouter\Auth\Error;
use SchenkeIo\LaravelAuthRouter\Contracts\UseExclusiveInterface;

/**
 * A collection for login providers and factory to create them from text.
 *
 * @extends Collection<int, BaseProvider>
 */
class ProviderCollection extends Collection
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['items'] ?? []);
    }

    /**
     * @param  string|array<int, string|BaseProvider>  $data
     */
    public static function fromTextArray(string|array $data): ProviderCollection
    {
        return ProviderFactory::fromTextArray($data);
    }

    public function handleExclusivity(): self
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

    public function sortProviders(): self
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
