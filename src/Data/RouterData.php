<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use Spatie\LaravelData\Data;

class RouterData extends Data
{
    public function __construct(
        public string $routeSuccess,
        public string $routeError,
        public string $routeHome,
        public bool $canAddUsers = true,
        public bool $rememberMe = false
    ) {}
}
