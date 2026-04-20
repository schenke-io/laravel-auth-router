<?php

namespace SchenkeIo\LaravelAuthRouter\Data;

use Carbon\Carbon;
use SchenkeIo\LaravelAuthRouter\Contracts\EmailConfirmInterface;

/**
 * Data structure for email confirmation.
 */
class EmailConfirmData implements EmailConfirmInterface
{
    public function __construct(
        public string $email,
        public string $token,
        public string $first_name,
        public string $last_name,
        public Carbon $expires_at
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return [
            'email' => $this->email,
            'token' => $this->token,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'expires_at' => $this->expires_at->toDateTimeString(),
        ];
    }
}
