<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use Spatie\LaravelData\Data;

/**
 * Simple data object carrying configuration for the auth router.
 */
class RouterData extends Data
{
    /**
     * @param  string[]  $middleware
     * @param  string[]  $errors
     */
    public function __construct(
        public string $routeSuccess,
        public string $routeError,
        public string $routeHome,
        public bool $canAddUsers = true,
        public bool $rememberMe = false,
        public string $prefix = '',
        public ?string $routeName = null,
        public ?string $emailConfirmClass = null,
        public array $middleware = [],
        public bool $showPayload = false,
        public ?string $logChannel = null,
        public bool $useProviderId = false,
        public ?string $impersonateGate = null,
        public string|\Closure|null $defaultName = null,
        public array $errors = []
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
            emailConfirmClass: $properties['emailConfirmClass'] ?? null,
            middleware: $properties['middleware'] ?? [],
            showPayload: $properties['showPayload'] ?? false,
            logChannel: $properties['logChannel'] ?? null,
            useProviderId: $properties['useProviderId'] ?? false,
            impersonateGate: $properties['impersonateGate'] ?? null,
            defaultName: $properties['defaultName'] ?? null,
            errors: $properties['errors'] ?? []
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

    /**
     * Middleware for guest-facing auth routes (login, callback, payload).
     *
     * @return string[]
     */
    public function guestMiddleware(): array
    {
        return array_merge(['web', 'guest'], $this->middleware);
    }

    /**
     * Middleware for authenticated auth routes (logout, impersonation).
     *
     * @return string[]
     */
    public function authMiddleware(): array
    {
        return array_merge(['web', 'auth'], $this->middleware);
    }
}
