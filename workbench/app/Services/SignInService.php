<?php

namespace Workbench\App\Services;

use SchenkeIo\LaravelAuthRouter\Auth\BaseProvider;

class SignInService
{
    /**
     * @param  array<int, string|BaseProvider>  $all
     * @param  string[]  $workOs
     * @param  string[]  $social
     * @param  string[]  $drivers
     */
    public function __construct(
        public readonly array $all,
        public readonly array $workOs,
        public readonly array $social,
        public readonly array $drivers,
    ) {}

    /**
     * @return array<int, string|BaseProvider>
     */
    public function all(): array
    {
        return $this->all;
    }

    /**
     * @return string[]
     */
    public function workOs(): array
    {
        return $this->workOs;
    }

    /**
     * @return string[]
     */
    public function social(): array
    {
        return $this->social;
    }

    /**
     * @return string[]
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }
}
