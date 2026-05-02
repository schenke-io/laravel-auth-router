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
        public bool $showPayload = false,
        public ?string $logChannel = null,
        public bool $useProviderId = false
    ) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self(
            routeSuccess: $properties['routeSuccess'],
            routeError: $properties['routeError'],
            routeHome: $properties['routeHome'],
            canAddUsers: $properties['canAddUsers'] ?? true,
            rememberMe: $properties['rememberMe'] ?? false,
            prefix: $properties['prefix'] ?? '',
            routeName: $properties['routeName'] ?? null,
            emailConfirm: $properties['emailConfirm'] ?? null,
            middleware: $properties['middleware'] ?? [],
            showPayload: $properties['showPayload'] ?? false,
            logChannel: $properties['logChannel'] ?? null,
            useProviderId: $properties['useProviderId'] ?? false
        );
    }

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
