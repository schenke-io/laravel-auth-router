<?php

namespace SchenkeIo\LaravelAuthRouter\Enums;

enum ErrorCategory: string
{
    case Configuration = 'configuration';
    case Network = 'network';
    case Account = 'account';
    case Session = 'session';
    case Provider = 'provider';
    case Unknown = 'unknown';

    public function recommendation(): string
    {
        return __('auth-router::errors.recommendation.'.$this->value);
    }
}
