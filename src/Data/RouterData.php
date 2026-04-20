<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use SchenkeIo\LaravelAuthRouter\Contracts\EmailConfirmInterface;
use Spatie\LaravelData\Data;

/**
 * Simple data object carrying configuration for the auth router.
 */
class RouterData extends Data
{
    /**
     * @param  string[]  $middleware
     */
    public function __construct(
        public string $routeSuccess,
        public string $routeError,
        public string $routeHome,
        public bool $canAddUsers = true,
        public bool $rememberMe = false,
        public string $prefix = '',
        public ?string $routeName = null,
        public ?EmailConfirmInterface $emailConfirm = null,
        public array $middleware = [],
        public bool $showPayload = false
    ) {}

    public function getRoutePrefix(): string
    {
        $prefix = $this->routeName ?? $this->prefix;

        return $prefix ? str_replace('/', '.', trim($prefix, '/.')).'.' : '';
    }

    public function getUriPrefix(): string
    {
        return $this->prefix ? trim($this->prefix, '/').'/' : '';
    }
}
