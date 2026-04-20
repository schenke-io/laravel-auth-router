<?php

namespace SchenkeIo\LaravelAuthRouter\Contracts;

/**
 * Interface for email confirmation data.
 */
interface EmailConfirmInterface
{
    public function getEmail(): string;

    public function getToken(): string;

    /**
     * @return array<string, mixed>
     */
    public function getData(): array;
}
